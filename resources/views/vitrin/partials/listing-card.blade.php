{{-- R1 — kart ayrışması onarımı (açık işler envanteri, 2026-08-02).

     Vitrin kartı (x-vitrin.listing-card) şimdiye dek YALNIZ ilan listesinde
     basılıyordu; profil, ana sayfa "yeni ilanlar", emlak, vasıta ve favoriler
     `partials.listing-card`'ı include ettiği için Vitrin temasında bile klasik
     kartı gösteriyordu. Bu aynı-ad override'ı sayesinde o altı yüzeyin hepsi
     tema aktifken kendiliğinden Vitrin kartına döner — kartın tek kaynağı
     component'te kalır, burada yalnız köprü var. --}}
<x-vitrin.listing-card :listing="$listing" />
