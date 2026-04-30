@php
    $viteEntryPoints = ['frontend/resources/css/app.css', 'frontend/resources/js/app.js'];
    $viteManifestPath = public_path('build/manifest.json');
    $hasViteAssets = file_exists(public_path('hot'));

    if (! $hasViteAssets && file_exists($viteManifestPath)) {
        $viteManifest = json_decode((string) file_get_contents($viteManifestPath), true);
        $hasViteAssets = is_array($viteManifest);

        foreach ($viteEntryPoints as $viteEntryPoint) {
            if (! array_key_exists($viteEntryPoint, $viteManifest ?? [])) {
                $hasViteAssets = false;
                break;
            }
        }
    }
@endphp

@if($hasViteAssets)
    @vite($viteEntryPoints)
@endif
