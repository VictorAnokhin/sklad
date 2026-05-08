@php
    $settingsTrans = Lang::get('settings');
    $i18nPayload = [
        'js' => $settingsTrans['js'] ?? [],
        'field_modes' => $settingsTrans['field_modes'] ?? [],
        'crud' => $settingsTrans['crud'] ?? [],
        'sitemap' => $settingsTrans['sitemap_modal'] ?? [],
        'accounts' => $settingsTrans['accounts'] ?? [],
        'catalog_modal' => $settingsTrans['catalog_modal'] ?? [],
    ];
@endphp
<script>
window.SettingsI18n = @json($i18nPayload);
function _ts(path) {
    const v = String(path || '').split('.').reduce(function (acc, key) {
        return acc && acc[key] !== undefined ? acc[key] : undefined;
    }, window.SettingsI18n);
    return v !== undefined && v !== null ? v : path;
}
</script>
