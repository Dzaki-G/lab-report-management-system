<h1>Edit Sampel</h1>

<form method="POST" action="{{ route('samples.update', $sample) }}">
    @csrf
    @method('PUT')

    <div>
        <label>Nama Sampel</label>
        <input name="sample_name" value="{{ $sample->sample_name }}" required>
    </div>

    <div>
        <label>Jumlah Sampel</label>
        <input type="number" name="sample_quantity" value="{{ $sample->sample_quantity }}" min="1" required>
    </div>

    <button type="submit">Update</button>
</form>

<a href="{{ route('form.show', $sample->form_pengujian_id) }}">Back</a>
