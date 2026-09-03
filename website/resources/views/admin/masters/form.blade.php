<x-admin-layout title="{{ $record ? 'Edit' : 'Add' }} {{ $definition['label'] }}" heading="Master Management">
    <div style="max-width:760px">
        <a href="{{ route('admin.masters.index', $master) }}" style="color:#465fff;font-weight:700">Back to {{ $definition['label'] }}</a>
        <section class="admin-card" style="padding:26px;margin-top:16px">
            <h2>{{ $record ? 'Edit' : 'Add' }} {{ str($definition['label'])->singular() }}</h2>
            <form method="POST" enctype="multipart/form-data" action="{{ $record ? route('admin.masters.update', [$master, $record->id]) : route('admin.masters.store', $master) }}" style="display:grid;gap:16px">
                @csrf
                @if($record) @method('PUT') @endif
                <label>Name<input class="admin-input" name="name" value="{{ old('name', $record?->name) }}" required></label>
                @if($master === 'countries')
                    <label>Country code<input class="admin-input" name="code" value="{{ old('code', $record?->code) }}" maxlength="2" required></label>
                    <label>Sort order<input class="admin-input" type="number" name="sort_order" value="{{ old('sort_order', $record?->sort_order ?? 0) }}" min="0"></label>
                @endif
                @if(in_array('description', $definition['fields']))
                    <label>Description<textarea class="admin-input" name="description" rows="4">{{ old('description', $record?->description) }}</textarea></label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <label>Lucide icon<input class="admin-input" name="icon" value="{{ old('icon', $record?->icon) }}"></label>
                        <label>Sort order<input class="admin-input" type="number" name="sort_order" value="{{ old('sort_order', $record?->sort_order ?? 0) }}"></label>
                    </div>
                    @if(in_array('icon_image', $definition['fields'], true))
                        <label>Icon image<input class="admin-input" type="file" name="icon_image" accept="image/*">@if($record?->icon_image_path)<img src="{{ asset($record->icon_image_path) }}" alt="Current icon" style="width:48px;height:48px;object-fit:contain;margin-top:8px">@endif</label>
                    @endif
                    <label><input type="checkbox" name="show_on_home" value="1" @checked(old('show_on_home', $record?->show_on_home))> Display on home page</label>
                @endif
                <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $record?->is_active ?? true))> Active</label>
                @if($errors->any())<p style="color:#b42318">{{ $errors->first() }}</p>@endif
                <button class="admin-button">Save {{ str($definition['label'])->singular() }}</button>
            </form>
        </section>
    </div>
</x-admin-layout>
