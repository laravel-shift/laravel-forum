<form method="POST" action="{{ $form_url }}" class="{{ isset($form_classes) ? $form_classes : '' }}">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" name="_method" value="{{ isset($method) ? $method : 'POST' }}">
    @if (isset($post) && !is_null($post))
        <input type="hidden" name="post_id" value="{{ $post->id }}">
    @endif

    @if (isset($show_title_field) && $show_title_field)
        <div class="form-group">
            <label for="title">{{ trans('forum::general.title') }}</label>
            <input type="text" name="title" value="{{ old('title') }}" class="form-control">
        </div>
    @endif

    <div class="form-group">
        <textarea name="content" class="form-control">{{ old('content') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary">{{ $submit_label }}</button>
    @if (isset($cancel_url))
        <a href="{{ $cancel_url }}" class="btn btn-default">{{ trans('forum::general.cancel') }}</a>
    @endif
</form>