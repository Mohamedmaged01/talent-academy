{{-- Permission matrix. Expects $selected (array of "module.action") and $holdable (what the current user may grant). --}}
<div class="table-wrap rounded-xl border border-line">
    <table class="table">
        <thead><tr><th>{{ __('Section') }}</th><th class="w-28 text-center">{{ __('View') }}</th><th class="w-28 text-center">{{ __('Manage') }}</th></tr></thead>
        <tbody>
            @foreach (\App\Support\Permissions::MODULES as $module => $label)
                <tr x-data="{ view: @js(in_array("$module.view", $selected, true) || in_array("$module.manage", $selected, true)), manage: @js(in_array("$module.manage", $selected, true)) }">
                    <td class="font-medium">{{ __($label) }}</td>
                    @foreach (['view', 'manage'] as $action)
                        @php($perm = "$module.$action")
                        <td class="text-center">
                            <input type="checkbox" name="{{ $name ?? 'permissions' }}[]" value="{{ $perm }}" aria-label="{{ __($label) }} — {{ __(ucfirst($action)) }}"
                                   x-model="{{ $action }}"
                                   @if ($action === 'manage') @change="if (manage) view = true" @else @change="if (! view) manage = false" @endif
                                   @disabled(! in_array($perm, $holdable, true))>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
