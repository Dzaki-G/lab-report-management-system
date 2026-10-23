<?php

namespace Tests\Feature;

class PhotoLinkTest extends LabTestCase
{
    public function test_holder_can_set_change_and_clear_the_photo_link(): void
    {
        $ph = $this->makeParameter('pH');
        $sp3 = $this->sp3For($this->createForm([$ph]), $ph);
        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $sp3));

        $this->actingAs($this->analisA)->post(route('analis.sp3.photo-link', $sp3), ['photo_link' => 'https://drive.google.com/ph'])
            ->assertSessionHas('success', 'Link foto berhasil disimpan.');
        $this->assertSame('https://drive.google.com/ph', $sp3->fresh()->photo_link);
        $this->assertNotNull($sp3->fresh()->photo_link_updated_at);

        $this->actingAs($this->analisA)->post(route('analis.sp3.photo-link', $sp3), ['photo_link' => ''])
            ->assertSessionHas('success', 'Link foto dihapus.');
        $this->assertNull($sp3->fresh()->photo_link);
    }

    public function test_other_analyst_cannot_set_it_and_invalid_urls_are_rejected(): void
    {
        $ph = $this->makeParameter('pH');
        $sp3 = $this->sp3For($this->createForm([$ph]), $ph);
        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $sp3));

        $this->actingAs($this->analisB)->post(route('analis.sp3.photo-link', $sp3), ['photo_link' => 'https://drive.google.com/b'])
            ->assertSessionHas('error');
        $this->actingAs($this->analisA)->post(route('analis.sp3.photo-link', $sp3), ['photo_link' => 'bukan link'])
            ->assertSessionHasErrors('photo_link');
        $this->assertNull($sp3->fresh()->photo_link);
    }

    public function test_link_can_still_be_set_after_lhp_is_generated(): void
    {
        $ph = $this->makeParameter('pH');
        $form = $this->createForm([$ph]);
        $sp3 = $this->sp3For($form, $ph);
        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $sp3));
        $form->update(['status' => 'selesai', 'lhp_google_file_id' => 'doc-123']);

        $this->actingAs($this->analisA)->post(route('analis.sp3.photo-link', $sp3), ['photo_link' => 'https://drive.google.com/late'])
            ->assertSessionHas('success', fn ($msg) => str_contains($msg, 'tidak mengubah dokumen LHP'));
        $this->assertSame('https://drive.google.com/late', $sp3->fresh()->photo_link);
    }

    public function test_lhp_text_only_lists_parameters_that_have_pictures(): void
    {
        $ph = $this->makeParameter('pH');
        $cod = $this->makeParameter('COD');
        $form = $this->createForm([$ph, $cod]);

        $this->assertSame('-', $form->fresh()->photoLinksText());

        $this->sp3For($form, $ph)->update(['photo_link' => 'https://drive.google.com/ph']);
        $this->assertSame('pH: https://drive.google.com/ph', $form->fresh()->photoLinksText());
    }

    public function test_admin_kirim_customer_page_shows_links_and_copy_button_only_when_present(): void
    {
        $ph = $this->makeParameter('pH');
        $cod = $this->makeParameter('COD');
        $form = $this->createForm([$ph, $cod]);
        $form->update(['status' => 'kirim_customer', 'lhp_google_file_id' => 'doc-123']);

        $this->actingAs($this->admin)->get(route('admin.lhp-ready'))
            ->assertOk()
            ->assertSee('Tidak ada link foto')
            ->assertDontSee('Salin semua');

        $this->sp3For($form, $cod)->update(['photo_link' => 'https://drive.google.com/cod']);

        $this->actingAs($this->admin)->get(route('admin.lhp-ready'))
            ->assertSee('https://drive.google.com/cod', false)
            ->assertSee('Salin semua')
            ->assertDontSee('Tidak ada link foto');

        $this->actingAs($this->admin)->get(route('form.show', $form))
            ->assertSee('https://drive.google.com/cod', false);
    }
}
