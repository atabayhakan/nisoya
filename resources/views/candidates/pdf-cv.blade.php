<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate->title }} - Nisoya CV</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; color: #1e293b; margin: 0; padding: 40px; background: #fff; }
        .header { border-bottom: 2px solid #0284c7; padding-bottom: 20px; margin-bottom: 25px; display: flex; justify-content: space-between; }
        .title { font-size: 24px; font-weight: bold; color: #0f172a; margin: 0; }
        .subtitle { font-size: 16px; color: #0284c7; margin-top: 4px; }
        .badge { background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: bold; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 16px; font-weight: bold; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; margin-bottom: 12px; }
        .skill-tag { display: inline-block; background: #f1f5f9; color: #334155; padding: 4px 10px; border-radius: 6px; font-size: 13px; margin-right: 6px; margin-bottom: 6px; }
        .footer { margin-top: 40px; border-top: 1px solid #e2e8f0; pt: 15px; text-align: center; font-size: 12px; color: #94a3b8; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="background: #0284c7; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer;">
            🖨️ PDF Olarak Yazdır / İndir
        </button>
    </div>

    <div class="header">
        <div>
            <h1 class="title">{{ $candidate->title }}</h1>
            <div class="subtitle">{{ $candidate->category->name ?? 'Gurbetçi Yetenek' }}</div>
            <div style="font-size: 13px; color: #64748b; margin-top: 6px;">
                📍 {{ $candidate->city }}, {{ $candidate->country_code }}
                @if($candidate->expected_salary) · 💰 {{ number_format($candidate->expected_salary) }} {{ $candidate->currency }} @endif
            </div>
        </div>
        <div>
            <span class="badge">Nisoya Onaylı CV</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Hakkımda & Deneyim</div>
        <p style="font-size: 14px; line-height: 1.6; white-space: pre-line;">{{ $candidate->description ?? 'Açıklama belirtilmemiş.' }}</p>
    </div>

    @if(!empty($candidate->skills))
        <div class="section">
            <div class="section-title">Yetenekler & Uzmanlıklar</div>
            <div>
                @foreach((array)$candidate->skills as $skill)
                    <span class="skill-tag">{{ $skill }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="footer">
        Nisoya Yurtdışı Türkler Platformu — https://www.nisoya.com
    </div>
</body>
</html>
