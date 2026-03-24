<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PairMobile extends Component
{
    public ?string $qrPayload = null;
    public ?string $qrSvg = null;
    public bool $showQr = false;

    /** @var \Laravel\Sanctum\NewAccessToken|null */
    private $accessToken = null;

    /** Tracks the token ID so we can revoke on cancel. */
    public ?int $tokenId = null;

    public function generateQr(): void
    {
        $user = auth()->user();

        $tokenName = 'mobile-app-' . now()->timestamp;

        $accessToken = $user->createToken($tokenName, ['*'], now()->addDays(30));

        $this->tokenId = $accessToken->accessToken->id;

        $payload = json_encode([
            'url' => config('app.url'),
            'token' => $accessToken->plainTextToken,
        ]);

        $this->qrPayload = $payload;
        $this->qrSvg = QrCode::format('svg')
            ->size(280)
            ->backgroundColor(39, 39, 42)  // zinc-800
            ->color(255, 255, 255)
            ->errorCorrection('M')
            ->generate($payload);
        $this->showQr = true;
    }

    public function revokeAndClose(): void
    {
        if ($this->tokenId) {
            auth()->user()->tokens()->where('id', $this->tokenId)->delete();
        }

        $this->reset(['qrPayload', 'qrSvg', 'showQr', 'tokenId']);
    }

    public function done(): void
    {
        $this->reset(['qrPayload', 'qrSvg', 'showQr', 'tokenId']);
    }

    public function render()
    {
        return view('livewire.servers.pair-mobile');
    }
}
