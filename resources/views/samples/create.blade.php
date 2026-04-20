<h1>Tambah Sampel</h1>

<form method="POST" action="{{ route('samples.store', $form) }}">
    @csrf

    <div>
        <label>Nama Sampel</label>
        <input name="sample_name" required>
    </div>

    <div>
        <label>Jumlah Sampel</label>
        <input type="number" name="sample_quantity" min="1" required>
    </div>

    <button type="submit">Simpan</button>
</form>

<a href="{{ route('form.show', $form) }}">Back</a>
