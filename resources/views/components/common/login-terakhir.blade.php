{{-- "Login Terakhir" cell — $waktu from the withLoginTerakhir() scope (App\Models\Concerns\PunyaLoginTerakhir). --}}
@props(['waktu' => null])

@if ($waktu)
    <span title="{{ $waktu->translatedFormat('d M Y, H:i') }}">{{ $waktu->diffForHumans() }}</span>
@else
    <span class="text-gray-400">Belum pernah</span>
@endif
