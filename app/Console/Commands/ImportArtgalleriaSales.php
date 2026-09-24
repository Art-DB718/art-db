<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\Contact;
use App\Models\Sale;
use App\Models\SaleLineItem;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Import artgalleria invoices as art-db Sale + SaleLineItem rows.
 *
 * The scrape gives us `contact_id` as a display string ("Ivana Moncoľová")
 * rather than a real id, and each line item's `artwork_id` is artgalleria's
 * internal artwork id — not our art-db id. We fall back to matching the
 * artwork by INV-* inventory_id extracted from the line item's description
 * (that's the last token on every artgalleria description).
 *
 * Dedupe on (owner_user_id, invoice_number) using the source `custom_id`
 * so re-runs update in place.
 */
class ImportArtgalleriaSales extends Command
{
    protected $signature = 'import:artgalleria-sales
        {json : Sales JSON from browser scraper}
        {--payments= : Optional payments JSON (from invoice list scrape) — sets paid_amount / payment_status / due_date-based overdue}
        {--user= : Owner user id}
        {--dry-run}';

    protected $description = 'Import artgalleria invoices as Sale + SaleLineItem records';

    public function handle(): int
    {
        $path = $this->argument('json');
        if (! is_file($path)) { $this->error("File not found: $path"); return 1; }
        $userId = (int) $this->option('user');
        $user = User::find($userId);
        if (! $user || $user->role !== UserRole::Gallery) {
            $this->error("User $userId not found or not Gallery"); return 1;
        }
        $dryRun = (bool) $this->option('dry-run');

        // Pre-build (owner-scoped) inventory_id → artwork lookup.
        $artByInv = Artwork::where('owner_user_id', $user->id)
            ->pluck('id', 'inventory_id')->toArray();

        // Optional payments overlay: build custom_id → {paid, status} map
        // from the invoice list scrape (edit page doesn't carry paid info).
        $payments = [];
        if ($p = $this->option('payments')) {
            if (! is_file($p)) { $this->error("Payments file not found: $p"); return 1; }
            foreach (json_decode(file_get_contents($p), true) as $row) {
                $key = trim((string) ($row['custom_id'] ?? ''));
                if ($key !== '') $payments[$key] = $row;
            }
        }

        $created = $updated = $failed = 0;
        foreach (json_decode(file_get_contents($path), true) as $i => $inv) {
            $invoiceNum = trim((string) ($inv['custom_id'] ?? '')) ?: ('AG-'.$inv['editId']);
            $items = $this->extractLineItems($inv);
            if (empty($items)) {
                $this->line(sprintf('  %3d. SKIP    inv=%s (no items)', $i+1, $invoiceNum));
                continue;
            }

            $buyerName = trim((string) ($inv['contact_id'] ?? ''));
            $buyer = null;
            if ($buyerName !== '') {
                $buyer = $this->findBuyer($user->id, $buyerName);
            }

            $subtotal = 0.0;
            $itemRows = [];
            foreach ($items as $it) {
                $qty   = (float) ($it['quantity']   ?? 1);
                $price = (float) ($it['unit_price'] ?? 0);
                $desc  = trim((string) ($it['description'] ?? ''));
                $inv2  = $this->extractInventoryId($desc);
                $artworkId = $inv2 && isset($artByInv[$inv2]) ? $artByInv[$inv2] : null;
                $lineTotal = round($qty * $price, 2);
                $subtotal += $lineTotal;
                $itemRows[] = compact('qty', 'price', 'desc', 'artworkId', 'lineTotal');
            }
            $taxRate = (float) ($inv['tax_percentage'] ?? 0);
            $taxAmt  = round($subtotal * ($taxRate / 100), 2);
            $total   = round($subtotal + $taxAmt, 2);

            // Overlay payment info if provided. Artgalleria's list view has
            // canonical 'Amount Paid' + 'Status' columns; edit page doesn't.
            $pay = $payments[$invoiceNum] ?? null;
            [$paidAmount, $paymentStatus] = $this->derivePayment($pay, $total);

            $attrs = [
                'invoice_number'    => $invoiceNum,
                'buyer_contact_id'  => $buyer?->id,
                'sale_date'         => $this->parseDate($inv['invoice_date'] ?? null),
                'due_date'          => $this->parseDate($inv['due_date'] ?? null),
                'currency'          => $this->parseCurrency($inv['currency_code'] ?? 'EUR'),
                'subtotal'          => $subtotal,
                'tax_rate'          => $taxRate,
                'tax_amount'        => $taxAmt,
                'discount_amount'   => (float) ($inv['discount_amount'] ?? 0),
                'total'             => $total,
                'paid_amount'       => $paidAmount,
                'payment_status'    => $paymentStatus,
                'owner_user_id'     => $user->id,
            ];

            $existing = Sale::where('owner_user_id', $user->id)
                ->where('invoice_number', $invoiceNum)->first();

            if ($dryRun) {
                $this->line(sprintf('  %3d. %s %s buyer=%s items=%d total=%s',
                    $i+1, $existing ? 'UPDATE' : 'CREATE', $invoiceNum,
                    $buyer?->last_name ?? '(unmatched)', count($itemRows), $total));
                $existing ? $updated++ : $created++;
                continue;
            }

            $sale = $existing ?: new Sale();
            $sale->fill($attrs)->save();
            // Wipe + rewrite items so re-runs are clean.
            $sale->lineItems()->delete();
            foreach ($itemRows as $pos => $r) {
                SaleLineItem::create([
                    'sale_id'     => $sale->id,
                    'artwork_id'  => $r['artworkId'],
                    'description' => $r['desc'],
                    'quantity'    => $r['qty'],
                    'unit_price'  => $r['price'],
                    'line_total'  => $r['lineTotal'],
                    'position'    => $pos + 1,
                ]);
            }
            $existing ? $updated++ : $created++;
        }

        $this->info(sprintf('Done: %d created, %d updated, %d failed', $created, $updated, $failed));
        return 0;
    }

    /** Flatten invoice[items][0..N] keys back into an ordered list. */
    protected function extractLineItems(array $inv): array
    {
        $items = [];
        foreach ($inv as $k => $v) {
            if (! str_starts_with($k, 'invoice_items_attributes.')) continue;
            [$_, $idx, $field] = explode('.', $k) + [null, null, null];
            if ($idx === null) continue;
            $items[(int) $idx][$field] = $v;
        }
        ksort($items);
        return array_values($items);
    }

    protected function extractInventoryId(string $desc): ?string
    {
        return preg_match('/INV-[A-Za-z0-9_-]+/', $desc, $m) ? $m[0] : null;
    }

    /**
     * Find a Contact from artgalleria's display blob. The scrape gives us
     * "Firstname Lastname  - email@x" (or just "Company"), so email is the
     * highest-signal identifier we can pull out; fall back to name / org
     * fold if no email or no email match.
     */
    protected function findBuyer(int $userId, string $blob): ?Contact
    {
        // 1) Email — cleanest match.
        if (preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', $blob, $m)) {
            $c = Contact::where('owner_user_id', $userId)->where('email', strtolower($m[0]))->first();
            if ($c) return $c;
        }
        // 2) Strip everything from " - " onwards + fold, try full name /
        //    last-name only / organization.
        $name = trim(preg_replace('/\s*-\s*[^-]*$/', '', $blob));
        $needle = $this->fold($name);
        if ($needle === '') return null;

        $all = Contact::where('owner_user_id', $userId)->get();
        foreach ($all as $c) {
            if ($this->fold(trim(($c->first_name ?? '').' '.($c->last_name ?? ''))) === $needle) return $c;
            if ($this->fold($c->organization ?? '') === $needle) return $c;
            if ($this->fold($c->last_name ?? '')   === $needle) return $c;
        }
        return null;
    }

    protected function fold(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';
        $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        return strtolower(preg_replace('/\s+/', ' ', $s));
    }

    /**
     * Translate the (paid, status) pair from the invoice list into
     * (paid_amount, payment_status). Handles the artgalleria quirk that
     * status='Paid' is set manually and often doesn't reflect
     * amount_paid — treat 'Paid' as fully paid regardless. Voided invoices
     * map to cancelled; overdue rows keep whatever was paid so far.
     */
    protected function derivePayment(?array $pay, float $total): array
    {
        if (! $pay) return [0.0, 'draft'];
        $status = strtolower(trim((string) ($pay['status'] ?? '')));
        $paidRaw = (float) preg_replace('/[^\d.-]/', '', (string) ($pay['amount_paid'] ?? '0'));

        if (str_starts_with($status, 'paid'))    return [max($paidRaw, $total), 'paid'];
        if (str_starts_with($status, 'voided'))  return [0.0, 'cancelled'];
        if (str_starts_with($status, 'overdue')) return [$paidRaw, 'overdue'];
        if ($paidRaw > 0 && $paidRaw < $total)   return [$paidRaw, 'partial'];
        if ($paidRaw >= $total && $total > 0)    return [$paidRaw, 'paid'];
        return [$paidRaw, 'draft'];
    }

    protected function parseDate(?string $s): ?string
    {
        if (! $s) return null;
        try { return Carbon::createFromFormat('d/m/Y', $s)->toDateString(); }
        catch (\Throwable $e) { return null; }
    }

    protected function parseCurrency(?string $s): string
    {
        if (! $s) return 'EUR';
        return preg_match('/[A-Z]{3}/', $s, $m) ? $m[0] : 'EUR';
    }
}
