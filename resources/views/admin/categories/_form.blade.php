@php
    $isEdit = $category->exists;
    $action = $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store');
@endphp

<form method="POST" action="{{ $action }}" class="card max-w-3xl space-y-4 p-5 sm:p-6">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name_ar" class="label">{{ __('fields.name_ar') }} *</label>
            <input id="name_ar" name="name_ar" value="{{ old('name_ar', $category->name_ar) }}" required class="input @error('name_ar') input-error @enderror">
            @error('name_ar')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="name_en" class="label">{{ __('fields.name_en') }} *</label>
            <input id="name_en" name="name_en" value="{{ old('name_en', $category->name_en) }}" required dir="ltr" class="input @error('name_en') input-error @enderror">
            @error('name_en')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="slug" class="label">{{ __('fields.slug') }}</label>
            <input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" dir="ltr" class="input" placeholder="auto-from-english-name">
        </div>
        <div>
            <label for="parent_id" class="label">{{ __('fields.parent_category') }}</label>
            <select id="parent_id" name="parent_id" class="input">
                <option value="">{{ __('admin.categories.no_parent') }}</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="position" class="label">{{ __('fields.position') }}</label>
            <input id="position" name="position" type="number" min="0" value="{{ old('position', $category->position ?? 0) }}" dir="ltr" class="input">
        </div>
        <div class="flex items-end pb-2">
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true)) class="h-4 w-4 rounded border-slate-300 text-brand-800 focus:ring-brand-600">
                {{ __('common.active') }}
            </label>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="description_ar" class="label">{{ __('fields.description_ar') }}</label>
            <textarea id="description_ar" name="description_ar" rows="4" class="input">{{ old('description_ar', $category->description_ar) }}</textarea>
        </div>
        <div>
            <label for="description_en" class="label">{{ __('fields.description_en') }}</label>
            <textarea id="description_en" name="description_en" rows="4" dir="ltr" class="input">{{ old('description_en', $category->description_en) }}</textarea>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary">{{ $isEdit ? __('common.save') : __('common.create') }}</button>
        <a href="{{ route('admin.categories.index') }}" class="btn-ghost">{{ __('common.cancel') }}</a>
    </div>
</form>
