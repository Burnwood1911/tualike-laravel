<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Guest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Encoders\PngEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Typography\FontFactory;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

const FONTS_GREAT_VIBES_REGULAR_TTF = 'fonts/GreatVibes-Regular.ttf';
class ImageService
{
    public function generateQrCode($string): ImageInterface
    {
        $qrCode = QrCode::format('png')
            ->size(200)
            ->generate($string);

        $tempImagePath = tempnam(sys_get_temp_dir(), 'qr_code').'.png';
        file_put_contents($tempImagePath, $qrCode);
        $qrImage = Image::read($tempImagePath);

        // Define the size of the white background
        $backgroundSize = 220; // Adjust as needed

        // Create a white background image
        $background = Image::create($backgroundSize, $backgroundSize)->fill('#ffffff');

        // Insert the QR code image into the white background, centered
        $background->place($qrImage, 'center');

        unlink($tempImagePath);

        return $background;
    }



    public function encode(Guest $guest, Card $card): string
    {

        $fileContents = Storage::disk('minio')->get($card->image);

        $cImage = Image::read($fileContents);

        $qrImage = $this->generateQrCode($guest->qr);

        $inviteTypeX = $card->invite_x;
        $inviteTypeY = $card->invite_y;

        if ($card->hide_qr == false) {

            if($card->id == 12) {
                $cImage->place($qrImage, $card->qr_position, 125);

            }else {
                $cImage->place($qrImage, $card->qr_position);

            }

        }

        $textImage = $this->createTextImage(ucwords(strtolower($guest->name)), $card->name_font_size, public_path(FONTS_GREAT_VIBES_REGULAR_TTF), $card->name_color);

        $textWidth = $textImage->width();
        $halfTextWidth = $textWidth / 2;

        $fullImageWidth = $cImage->width();
        $halfFullImageWidth = $fullImageWidth / 2;

        $ultraOffset = $halfFullImageWidth - $halfTextWidth - $card->x_offset;

        $cImage->place($textImage, 'top-left', $ultraOffset, $card->name_y);

        // $cImage->text(ucwords(strtolower($guest->name)), $nameX, $nameY, function (FontFactory $font) use ($card) {
        //     $font->filename(public_path(FONTS_GREAT_VIBES_REGULAR_TTF));
        //     $font->align('center');
        //     $font->valign('middle');
        //     $font->size($card->name_font_size);
        //     $font->color($card->name_color);
        // });

        if ($guest->guest_type != 'NONE') {

            $cImage->text(ucwords(strtolower($guest->guest_type)), $inviteTypeX, $inviteTypeY, function (FontFactory $font) use ($card) {
                $font->filename(public_path(FONTS_GREAT_VIBES_REGULAR_TTF));
                $font->size($card->invite_font_size);
                $font->color($card->type_color);
            });
        }

        $imageBytes = (string) $cImage->encode(new PngEncoder());
        $unique = Str::random(8);
        $filename = Str::slug($guest->name).'-'.$unique.'.png';

        Storage::disk('minio')->put($filename, $imageBytes, 'public');

        return "https://minio.alexrossi.xyz/tualike/$filename";
    }



    public function createTextImage($text, $fontSize, $fontPath, $hexColor)
    {
        $img = Image::create(1, 1);

        $img->text($text, 0, 0, function ($font) use ($fontSize, $fontPath, $hexColor) {
            $font->file($fontPath);
            $font->size($fontSize);
            $font->color($hexColor);
            $font->valign('top');
            $font->align('left');
        });

        $box = imagettfbbox($fontSize, 0, $fontPath, $text);
        $width = abs($box[4] - $box[0]);
        $height = abs($box[5] - $box[1]);

        $img = Image::create($width, $height);
        $img->text($text, 0, 0, function ($font) use ($fontSize, $fontPath, $hexColor) {
            $font->file($fontPath);
            $font->size($fontSize);
            $font->color($hexColor);
            $font->valign('top');
            $font->align('left');
        });

        return $img;
    }
}
