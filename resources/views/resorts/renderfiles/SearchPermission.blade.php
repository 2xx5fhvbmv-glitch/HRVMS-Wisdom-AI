
<div class="accordion-item">

@if(!empty($ModuleWisePermission))
    @foreach ($ModuleWisePermission as $moduleId => $data)
        @php
            // Use a loop counter to make the first item open by default
            static $isFirst = true;
        @endphp

            <h2 class="accordion-header" id="headingOne{{ $moduleId }}">
                <button class="accordion-button {{ $isFirst ? '' : 'collapsed' }}"  type="button" data-bs-toggle="collapse"  data-bs-target="#collapseOne{{ $moduleId }}" aria-expanded="true" aria-controls="collapseOne">


                    @if(isset($data['module']->name))
                    {{ $data['module']->name }}
                    @else
                        Module not found
                    @endif
                </button>
            </h2>
            <div id="collapseOne{{ $moduleId }}" class="accordion-collapse collapse  {{ $isFirst ? 'show' : '' }}" aria-labelledby="headingOne{{ $moduleId }}" data-bs-parent="#accordionPermissions">
                <div class="table-responsive">
                    @if(isset($data['permissions']))

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Page Name</th>
                                    <th>Select All</th>
                                    <th>View</th>
                                    <th>Create</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                    <th>Self</th>
                                    <th>Assign</th>
                                    <th>Approve</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['permissions'] as $permission)
                                    @if(!empty($data['permissions']))


                                        <tr>
                                            <td>
                                                @if(isset($permission['module_page']))
                                                    {{ $permission['module_page']->page_name }}
                                                @else
                                                    Page Not found Please Contact To Super Admin
                                                @endif
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input Resort_parent_checkbox" data-id="{{$permission['module_page']->id}}_{{ $moduleId }}" id="parent_{{$permission['module_page']->id}}_{{ $moduleId }}" name="module_permissions_parent" type="checkbox" value=""
                                                        @if(!empty($ModuleWiseExitingPermissions) && array_key_exists($permission['module_page']->id,$ModuleWiseExitingPermissions));
                                                        checked
                                                        @endif>
                                                </div>

                                                @if(!empty(config('settings.resort_permissions')))
                                                    @foreach (config('settings.resort_permissions') as $key => $internal)
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input  child_parent_{{$permission['module_page']->id}}_{{ $moduleId }}" id="child_{{ $permission['module_page']->id }}" name="Resort_page_permissions[{{ $permission['module_page']->id}}][]" type="checkbox" value="{{$internal}}"
                                                                @if(!empty($ModuleWiseExitingPermissions) && array_key_exists($permission['module_page']->id,$ModuleWiseExitingPermissions) && in_array($internal,$ModuleWiseExitingPermissions[$permission['module_page']->id]));
                                                                checked

                                                                @endif
                                                            >
                                                            {{ ucfirst($key) }}
                                                        </div>
                                                    </td>
                                                    @endforeach
                                                @endif
                                        </tr>
                                    @else
                                    <tr>
                                        <td colspan="9">  <p>No permissions found for this module.</p> </td>
                                    </tr>
                                    @endif

                                @endforeach


                            </tbody>
                        </table>
                    @else
                        <p>No permissions found for this module.</p>
                    @endif

                </div>
            </div>
        </div>
    @endforeach
@else
    <h5><p style="text-align:center">No permissions found for this module.</p></h5>
@endif
</div>
