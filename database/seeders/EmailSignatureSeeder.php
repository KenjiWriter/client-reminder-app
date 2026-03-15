<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class EmailSignatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $signatureHtml = '<table cellpadding="0" cellspacing="0" border="0" style="font-family: Arial, sans-serif; color: #374151; font-size: 14px; line-height: 1.6;">
    <tr>
        <td style="padding-bottom: 12px;">
            <div style="font-size: 16px; font-weight: bold; color: #111827;">Emilia Wiśniewska</div>
            <div style="font-size: 14px; color: #10B981; font-style: italic;">Face modeling</div>
        </td>
    </tr>
    <tr>
        <td style="border-top: 1px solid #D1D5DB; padding-top: 12px;">
            <div style="font-size: 13px; color: #4B5563;">
                <span style="color: #10B981; margin-right: 6px;">📍</span> Emilianów 8A, 96-330<br>
                <span style="color: #10B981; margin-right: 6px;">📞</span> Tel: 793 173 748<br>
                <span style="color: #10B981; margin-right: 6px;">🌐</span> <a href="http://www.face-modeling.pl" style="color: #10B981; text-decoration: none;">www.face-modeling.pl</a><br>
                <div style="color: #9CA3AF; font-size: 12px; margin-top: 6px;">NIP: 8381777504</div>
            </div>
        </td>
    </tr>
</table>';

        $setting = Setting::first();
        if (!$setting) {
            $setting = Setting::create(['key' => 'global_settings', 'value' => null]);
        }
        
        $setting->update(['email_signature' => $signatureHtml]);
    }
}
