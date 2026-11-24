@extends('layouts.admin')

@section('title', __('admin.categories.title'))
@section('page_title', __('admin.categories.title'))
@section('page_subtitle', __('admin.categories.subtitle'))

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ __('admin.categories.intro') }}</p>
        @can('products.manage')
            <a href="{{ route('admin.categories.create') }}" class="btn-primary">{{ __('admin.categories.add') }}</a>
        @endcan
    </div>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
                <tr>
                    <th>{{ __('fields.name_ar') }}</th>
                    <th>{{ __('fields.name_en') }}</th>
                    <th>{{ __('admin.categories.parent') }}</th>
                    <th class="text-center">{{ __('admin.categories.products') }}</th>
                    <th class="text-center">{{ __('fields.position') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            <p class="font-bold text-slate-900">{{ $category->name_ar }}</p>
                            <p class="text-[11px] text-slate-400" dir="ltr">{{ $category->slug }}</p>
                        </td>
                        <td class="text-sm text-slate-600" dir="ltr">{{ $category->name_en }}</td>
                        <td class="text-sm text-slate-600">{{ $category->parent?->name ?? '—' }}</td>
                        <td class="text-center font-semibold">{{ $category->products_count }}</td>
                        <td class="text-center text-sm text-slate-500">{{ $category->position }}</td>
                        <td>
                            @if ($category->is_active)
                                <span class="badge-success">{{ __('common.active') }}</span>
                            @else
                                <span class="badge-neutral">{{ __('common.inactive') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @can('products.manage')
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn-outline btn-sm">{{ __('common.edit') }}</a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" x-data="confirmForm" data-message="{{ __('admin.categories.confirm_delete') }}" @submit="onConfirm($event)">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm">{{ __('common.delete') }}</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('admin.categories.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $categories->links() }}</div>
@endsection
