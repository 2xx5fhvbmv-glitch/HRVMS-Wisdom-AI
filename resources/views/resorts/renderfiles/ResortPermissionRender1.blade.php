

    @if(!empty($ModuleWisePermission))
        @foreach ($ModuleWisePermission as $moduleId => $data)
            @php
                // Use a loop counter to make the first item open by default
                static $isFirst = true;
            @endphp
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne{{ $moduleId }}">
                    <button class="accordion-button {{ $isFirst ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne{{ $moduleId }}" aria-expanded="{{ $isFirst ? 'true' : 'false' }}" aria-controls="collapseOne{{ $moduleId }}">
                        @if(isset($data['module']))
                            {{ $data['module']->module_name }}
                        @else
                            Module not found
                        @endif
                    </button>
                </h2>
                <div id="collapseOne{{ $moduleId }}" class="accordion-collapse collapse {{ $isFirst ? 'show' : '' }}" aria-labelledby="headingOne{{ $moduleId }}" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        @if(isset($data['permissions']))
                            <table class="table table-responsive">
                                <thead>
                                    <tr>
                                        <th>Page Name </th>
                                        <th>Select All</th>
                                        <th>Permission </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <ul>
                                        @foreach($data['permissions'] as $permission)
                                            <tr>
                                                <td>
                                                    <li>
                                                        @if(isset($permission['module_page']))
                                                            {{ $permission['module_page']->page_name }}
                                                        @else
                                                            Page Not found Please Contact To Super Admin
                                                        @endif
                                                    </li>
                                                </td>
                                                <td>
                                                    <div class="form-check">
                                                        <label class="form-check-label" for="create-{{$permission['module_page']->id}}_{{ $moduleId }}">
                                                            <input class="form-check-input Resort_parent_checkbox" data-id="{{$permission['module_page']->id}}_{{ $moduleId }}" id="parent_{{$permission['module_page']->id}}_{{ $moduleId }}" name="module_permissions_parent" type="checkbox" value=""


                                                                    @if(!empty($ModuleWiseExitingPermissions) && array_key_exists($permission['module_page']->id,$ModuleWiseExitingPermissions));
                                                                    checked

                                                                    @endif
                                                            >

                                                        </label>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if(!empty(config('settings.resort_permissions')))
                                                        @foreach (config('settings.resort_permissions') as $key => $internal)
                                                            <div class="form-check">
                                                                <label class="form-check-label" for="child-{{$permission['module_page']->id}}_{{ $moduleId }}">
                                                                    <input class="form-check-input child-checkbox child_parent_{{$permission['module_page']->id}}_{{ $moduleId }}" id="child_{{ $permission['module_page']->id }}" name="Resort_page_permissions[{{ $permission['module_page']->id}}][]" type="checkbox" value="{{$internal}}"
                                                                    @if(!empty($ModuleWiseExitingPermissions) && array_key_exists($permission['module_page']->id,$ModuleWiseExitingPermissions) && in_array($internal,$ModuleWiseExitingPermissions[$permission['module_page']->id]));
                                                                    checked

                                                                    @endif
                                                                    >
                                                                    {{ ucfirst($key) }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </ul>
                                </tbody>
                            </table>
                        @else
                            <p>No permissions found for this module.</p>
                        @endif
                    </div>
                </div>
            </div>
            @php
                $isFirst = false; // After the first item, set this to false so other accordions are collapsed
            @endphp
        @endforeach
    @endif

