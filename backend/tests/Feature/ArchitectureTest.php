<?php

use Illuminate\Support\Facades\File;

it('keeps controllers thin and free of infrastructure facades', function () {
    $controllers = File::allFiles(app_path('Http/Controllers'));

    expect($controllers)->not->toBeEmpty();

    foreach ($controllers as $file) {
        if (in_array($file->getFilename(), ['HealthController.php', 'Controller.php'], true)) {
            continue;
        }

        $source = File::get($file->getPathname());

        expect($source)
            ->not->toMatch('/\b(DB|Cache|Http|Redis|Storage|Mail)::/')
            ->and($source)->toContain('extends Controller');
    }
});

it('keeps module services within their own module or an explicitly shared module', function () {
    $shared = ['Notifications'];
    $modules = File::directories(app_path('Modules'));

    expect($modules)->not->toBeEmpty();

    foreach ($modules as $modulePath) {
        $module = basename($modulePath);
        $services = File::allFiles($modulePath.'/Services');

        foreach ($services as $file) {
            $source = File::get($file->getPathname());

            preg_match_all('/use App\\\\Modules\\\\([A-Za-z]+)\\\\/', $source, $matches);

            foreach ($matches[1] as $referencedModule) {
                expect([$module, ...$shared])->toContain($referencedModule);
            }
        }
    }
});
