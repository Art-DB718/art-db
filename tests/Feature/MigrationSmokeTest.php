<?php
test('migrations run on sqlite', function () {
    expect(\Illuminate\Support\Facades\Schema::hasTable('users'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasColumn('users', 'trial_reminders_sent'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasColumn('users', 'storage_used_bytes'))->toBeTrue();
});
