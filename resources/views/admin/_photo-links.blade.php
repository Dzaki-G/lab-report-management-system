{{-- Photo links for a form (only parameters that have pictures) + "Salin semua" for sending to the customer --}}
@php
    $withPhoto = $form->sp3sWithPhotoLink();
    $copyText = 'LHP ' . ($form->lhp_number ?? $form->no_terima_sampel ?? '') . "\nLink foto:\n" . $form->photoLinksText();
@endphp
<div class="space-y-1 text-xs">
    @forelse($withPhoto as $sp3)
        <div class="flex items-center gap-1.5">
            <span class="font-medium text-gray-700">{{ $sp3->parameter->name ?? '-' }}:</span>
            <a href="{{ $sp3->photo_link }}" target="_blank" class="text-blue-600 hover:underline">Buka</a>
        </div>
    @empty
        <span class="text-gray-400">Tidak ada link foto</span>
    @endforelse

    @if($withPhoto->isNotEmpty())
        <button type="button"
                data-copy="{{ $copyText }}"
                onclick="navigator.clipboard.writeText(this.dataset.copy).then(() => { const t = this.textContent; this.textContent = '✓ Tersalin'; setTimeout(() => this.textContent = t, 1500); })"
                class="mt-1 px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium">
            Salin semua
        </button>
    @endif
</div>
