<h1>Samples for Form {{ $form->form_number }}</h1>

@if(session('success'))
    <p>{{ session('success') }}</p>
@endif

<h3>Add Sample</h3>

<form method="POST" action="{{ route('samples.store', $form) }}">
    @csrf

    <div>
        <label>Sample Code</label>
        <input name="sample_code" required>
    </div>

    <div>
        <label>Sample Name</label>
        <input name="sample_name" required>
    </div>

    <div>
        <label>Description</label>
        <textarea name="description"></textarea>
    </div>

    <button type="submit">Add Sample</button>
</form>

<hr>

<h3>Existing Samples</h3>

<table border="1" cellpadding="8">
    <tr>
        <th>Code</th>
        <th>Name</th>
        <th>Description</th>
    </tr>

    @foreach($samples as $sample)
        <tr>
            <td>{{ $sample->sample_code }}</td>
            <td>{{ $sample->sample_name }}</td>
            <td>{{ $sample->description }}</td>
        </tr>
    @endforeach
</table>

<a href="{{ route('form.index') }}">Back to Forms</a>
