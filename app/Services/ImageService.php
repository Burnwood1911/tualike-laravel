<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Guest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Encoders\PngEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Typography\FontFactory;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ImageService
{
    public function generateQrCode($string)
    {
        $qrCode = QrCode::format('png')
            ->size(250)
            ->generate($string);

        $tempImagePath = tempnam(sys_get_temp_dir(), 'qr_code').'.png';
        file_put_contents($tempImagePath, $qrCode);
        $qrImage = Image::read($tempImagePath);

        // Define the size of the white background
        $backgroundSize = 300; // Adjust as needed

        // Create a white background image
        $background = Image::create($backgroundSize, $backgroundSize)->fill('#ffffff');

        // Insert the QR code image into the white background, centered
        $background->place($qrImage, 'center');

        unlink($tempImagePath);

        return $background;
    }
    // public function generateQrCode($string)
    // {
    //     $qrCode = QrCode::format('png')
    //         ->size(250)
    //         ->generate($string);

    //     $tempImagePath = tempnam(sys_get_temp_dir(), 'qr_code').'.png';
    //     file_put_contents($tempImagePath, $qrCode);
    //     $qrImage = Image::read($tempImagePath);

    //     unlink($tempImagePath);

    //     return $qrImage;
    // }

    public function encode(Guest $guest, Card $card)
    {

        $fileContents = Storage::disk('minio')->get($card->image);

        $cImage = Image::read($fileContents);

        $qrImage = $this->generateQrCode($guest->qr);

        // $axisStart = $card->name_start_x;
        // $axisEnd = $card->name_end_x;
        // $stringStartPosition = $this->calculateStringCenterPosition($axisStart, $axisEnd, preg_replace('/\s+/', '', ucwords(strtolower($guest->name))), $card->name_font_size);

        //

        $targetX1 = $card->name_start_x;
        $targetX2 = $card->name_end_x;
        $targetY = $card->name_y;

        $bbox = imagettfbbox($card->name_font_size, 0, public_path('fonts/GreatVibes-Regular.ttf'), ucwords(strtolower($guest->name)));

        $textWidth = $bbox[2] - $bbox[0];

        $targetWidth = $targetX2 - $targetX1;
        $x = $targetX1 + ($targetWidth - $textWidth) / 2;

        $nameX = $x;
        $nameY = $card->name_y;

        $inviteTypeX = $card->invite_x;
        $inviteTypeY = $card->invite_y;

        $cImage->place($qrImage, $card->qr_position);

        $cImage->text(ucwords(strtolower($guest->name)), $nameX, $nameY, function (FontFactory $font) use ($card) {
            $font->filename(public_path('fonts/GreatVibes-Regular.ttf'));
            $font->align('center');
            $font->valign('middle');
            $font->size($card->name_font_size);
            $font->color($card->name_color);
        });

        if (! is_null($guest->guest_type)) {

            $cImage->text(ucwords(strtolower($guest->guest_type)), $inviteTypeX, $inviteTypeY, function (FontFactory $font) use ($card) {
                $font->filename(public_path('fonts/GreatVibes-Regular.ttf'));
                $font->size($card->invite_font_size);
                $font->color($card->type_color);
            });
        }

        $imageBytes = (string) $cImage->encode(new PngEncoder());
        $unique = Str::random(8);
        $filename = Str::slug($guest->name).'-'.$unique.'.png';

        Storage::disk('minio')->put($filename, $imageBytes, 'public');

        $url = "https://minio.alexrossi.xyz/tualike/$filename";

        return $url;
    }

    public function calculateStringCenterPosition($axisStart, $axisEnd, $string, $fontSize)
    {

        $axisWidth = $axisEnd - $axisStart;
        $axisMidpoint = $axisStart + $axisWidth / 2;

        $stringWidth = self::calculateStringWidth($string, $fontSize, public_path('fonts/GreatVibes-Regular.ttf'));
        $stringStart = $axisMidpoint - $stringWidth / 2;

        return $stringStart;
    }

    private function calculateStringWidth($string, $fontSize, $fontFile)
    {
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $string);
        $stringWidth = $bbox[2] - $bbox[0];

        return $stringWidth;
    }
}
