<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Tracer Study Alumni</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #1a1a1a;
            line-height: 1.5;
        }

        /* ===== COVER PAGE ===== */
        .cover {
            width: 100%;
            text-align: center;
            padding: 60px 40px;
            page-break-after: always;
        }

        .cover-logo-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #006747;
            margin: 0 auto 20px auto;
            display: block;
        }

        .cover-university {
            font-size: 13pt;
            font-weight: bold;
            color: #006747;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .cover-tagline {
            font-size: 9pt;
            color: #555555;
            margin-bottom: 40px;
        }

        .cover-divider {
            width: 80px;
            height: 3px;
            background-color: #006747;
            margin: 0 auto 40px auto;
        }

        .cover-title {
            font-size: 18pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cover-subtitle {
            font-size: 12pt;
            color: #444444;
            margin-bottom: 8px;
        }

        .cover-period {
            font-size: 10pt;
            color: #666666;
            margin-bottom: 40px;
        }

        .cover-meta {
            font-size: 9pt;
            color: #888888;
            margin-top: 60px;
        }

        /* ===== GENERAL LAYOUT ===== */
        .page-header {
            border-bottom: 2px solid #006747;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        .page-header h1 {
            font-size: 11pt;
            color: #006747;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section {
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #006747;
            border-left: 3px solid #006747;
            padding-left: 8px;
            margin-bottom: 10px;
        }

        /* ===== SUMMARY CARDS ===== */
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .summary-grid td {
            width: 20%;
            padding: 10px 8px;
            text-align: center;
            border: 1px solid #e0e0e0;
            background-color: #f9f9f9;
        }

        .summary-value {
            font-size: 16pt;
            font-weight: bold;
            color: #006747;
            display: block;
        }

        .summary-label {
            font-size: 8pt;
            color: #555555;
            display: block;
            margin-top: 2px;
        }

        /* ===== TABLES ===== */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        table.data-table thead tr {
            background-color: #006747;
            color: #ffffff;
        }

        table.data-table thead th {
            padding: 7px 8px;
            text-align: left;
            font-weight: bold;
        }

        table.data-table thead th.text-right {
            text-align: right;
        }

        table.data-table thead th.text-center {
            text-align: center;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #f4faf7;
        }

        table.data-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        table.data-table tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e8e8e8;
            vertical-align: top;
        }

        table.data-table tbody td.text-right {
            text-align: right;
        }

        table.data-table tbody td.text-center {
            text-align: center;
        }

        table.data-table tfoot tr {
            background-color: #edf5f1;
            font-weight: bold;
        }

        table.data-table tfoot td {
            padding: 6px 8px;
            border-top: 2px solid #006747;
        }

        /* ===== FOOTER ===== */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 24px;
            border-top: 1px solid #cccccc;
            font-size: 8pt;
            color: #888888;
            text-align: center;
            padding-top: 4px;
        }

        /* ===== PAGE BREAK ===== */
        .page-break {
            page-break-before: always;
        }

        /* ===== BADGE ===== */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .badge-selesai    { background-color: #d4edda; color: #155724; }
        .badge-draft      { background-color: #fff3cd; color: #856404; }
        .badge-terkirim   { background-color: #cce5ff; color: #004085; }
        .badge-default    { background-color: #e2e3e5; color: #383d41; }

        .text-muted { color: #888888; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mt-8 { margin-top: 8px; }
        .mb-4 { margin-bottom: 4px; }
    </style>
</head>
<body>

{{-- ===== FOOTER (fixed, muncul di semua halaman) ===== --}}
<div class="footer">
    {{ $university_name }} &nbsp;|&nbsp; Laporan Tracer Study Alumni &nbsp;|&nbsp;
    Digenerate: {{ \Carbon\Carbon::parse($generated_at)->format('d/m/Y H:i') }} WITA
</div>

{{-- ===== HALAMAN COVER ===== --}}
<div class="cover">
    <div class="cover-logo-placeholder"></div>
    <div class="cover-university">{{ $university_name }}</div>
    <div class="cover-tagline">{{ $university_tagline }}</div>
    <div class="cover-divider"></div>
    <div class="cover-title">Laporan Tracer Study</div>
    <div class="cover-title">Alumni</div>

    @if($period)
        <div class="cover-subtitle" style="margin-top: 16px;">
            Periode: {{ $period->name }}
        </div>
        <div class="cover-period">
            {{ \Carbon\Carbon::parse($period->start_date)->format('d M Y') }}
            &ndash;
            {{ \Carbon\Carbon::parse($period->end_date)->format('d M Y') }}
        </div>
    @else
        <div class="cover-subtitle" style="margin-top: 16px;">Semua Periode</div>
    @endif

    <div class="cover-meta">
        Digenerate pada:
        {{ \Carbon\Carbon::parse($generated_at)->setTimezone('Asia/Makassar')->format('d F Y, H:i') }} WITA
    </div>
</div>

{{-- ===== HALAMAN KONTEN ===== --}}
<div class="page-header">
    <h1>Laporan Tracer Study Alumni &mdash; {{ $university_name }}</h1>
</div>

{{-- Section 1: Ringkasan Eksekutif --}}
<div class="section">
    <div class="section-title">1. Ringkasan Eksekutif</div>
    <table class="summary-grid">
        <tr>
            <td>
                <span class="summary-value">{{ number_format($total_alumni) }}</span>
                <span class="summary-label">Total Alumni</span>
            </td>
            <td>
                <span class="summary-value">{{ number_format($completed_responses) }}</span>
                <span class="summary-label">Respons Masuk</span>
            </td>
            <td>
                <span class="summary-value">{{ number_format($response_rate, 1) }}%</span>
                <span class="summary-label">Tingkat Respons</span>
            </td>
            <td>
                <span class="summary-value">{{ number_format($employment_rate, 1) }}%</span>
                <span class="summary-label">Tingkat Keterserapan</span>
            </td>
            <td>
                @if($average_waiting !== null)
                    <span class="summary-value">{{ number_format($average_waiting, 1) }}</span>
                    <span class="summary-label">Rata-rata Masa Tunggu (bln)</span>
                @else
                    <span class="summary-value text-muted">—</span>
                    <span class="summary-label">Rata-rata Masa Tunggu (bln)</span>
                @endif
            </td>
        </tr>
    </table>
    <table class="summary-grid" style="margin-top: 4px;">
        <tr>
            <td style="width: 20%;">
                <span class="summary-value">{{ number_format($relevance_rate, 1) }}%</span>
                <span class="summary-label">Relevansi Kerja–Studi</span>
            </td>
            <td style="width: 80%; text-align: left; padding-left: 16px; background: #fff; border: 1px solid #e0e0e0;">
                <span style="font-size: 9pt; color: #555;">
                    Laporan ini mencakup data alumni yang terdaftar dalam sistem SITRAS UNISYA.
                    @if($period)
                        Periode aktif: <strong>{{ $period->name }}</strong>
                        ({{ \Carbon\Carbon::parse($period->start_date)->format('d M Y') }}
                        &ndash;
                        {{ \Carbon\Carbon::parse($period->end_date)->format('d M Y') }}).
                    @endif
                    Data diambil secara real-time pada saat laporan digenerate.
                </span>
            </td>
        </tr>
    </table>
</div>

{{-- Section 2: Distribusi Industri --}}
@if(count($by_industry) > 0)
<div class="section">
    <div class="section-title">2. Distribusi Sektor Industri</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th>Sektor Industri</th>
                <th class="text-right" style="width: 15%;">Jumlah</th>
                <th class="text-right" style="width: 15%;">Persentase</th>
            </tr>
        </thead>
        <tbody>
            @foreach($by_industry as $i => $item)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $item['sector'] }}</td>
                <td class="text-right">{{ number_format($item['count']) }}</td>
                <td class="text-right">{{ number_format($item['percentage'], 1) }}%</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ number_format(array_sum(array_column($by_industry, 'count'))) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

{{-- Section 3: Distribusi Rentang Gaji --}}
@if(count($by_salary) > 0)
<div class="section">
    <div class="section-title">3. Distribusi Rentang Gaji Pertama</div>
    @php
        $salaryLabels = [
            'lt_1jt'  => '< Rp 1.000.000',
            '1_3jt'   => 'Rp 1.000.000 – Rp 3.000.000',
            '3_5jt'   => 'Rp 3.000.000 – Rp 5.000.000',
            'gt_5jt'  => '> Rp 5.000.000',
        ];
    @endphp
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th>Rentang Gaji</th>
                <th class="text-right" style="width: 15%;">Jumlah</th>
                <th class="text-right" style="width: 15%;">Persentase</th>
            </tr>
        </thead>
        <tbody>
            @foreach($by_salary as $i => $item)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $salaryLabels[$item['range']] ?? $item['range'] }}</td>
                <td class="text-right">{{ number_format($item['count']) }}</td>
                <td class="text-right">{{ number_format($item['percentage'], 1) }}%</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ number_format(array_sum(array_column($by_salary, 'count'))) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

{{-- Section 4: Per Program Studi --}}
@if(count($by_study_program) > 0)
<div class="section">
    <div class="section-title">4. Tingkat Keterserapan per Program Studi</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th>Program Studi</th>
                <th class="text-center" style="width: 12%;">Kode</th>
                <th class="text-right" style="width: 12%;">Total</th>
                <th class="text-right" style="width: 12%;">Bekerja</th>
                <th class="text-right" style="width: 12%;">Tingkat (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($by_study_program as $i => $item)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $item['name'] }}</td>
                <td class="text-center">{{ $item['code'] }}</td>
                <td class="text-right">{{ number_format($item['total']) }}</td>
                <td class="text-right">{{ number_format($item['employed']) }}</td>
                <td class="text-right">{{ number_format($item['rate'], 1) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Section 5: Daftar Alumni --}}
@if($alumni->count() > 0)
<div class="section page-break">
    <div class="page-header" style="margin-top: 0;">
        <h1>Laporan Tracer Study Alumni — {{ $university_name }}</h1>
    </div>
    <div class="section-title">5. Daftar Alumni</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 12%;">NIM</th>
                <th>Nama Lengkap</th>
                <th style="width: 18%;">Program Studi</th>
                <th class="text-center" style="width: 8%;">Angkatan</th>
                <th class="text-right" style="width: 8%;">IPK</th>
                <th class="text-center" style="width: 12%;">Status Survei</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumni as $i => $alum)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $alum->nim }}</td>
                <td>{{ $alum->full_name }}</td>
                <td>{{ $alum->studyProgram->name ?? '-' }}</td>
                <td class="text-center">{{ $alum->graduationYear->year ?? '-' }}</td>
                <td class="text-right">{{ $alum->gpa !== null ? number_format((float)$alum->gpa, 2) : '-' }}</td>
                <td class="text-center">
                    @php
                        $statusMap = [
                            'selesai'          => ['label' => 'Selesai',  'class' => 'badge-selesai'],
                            'sedang_mengisi'   => ['label' => 'Proses',   'class' => 'badge-draft'],
                            'terkirim'         => ['label' => 'Terkirim', 'class' => 'badge-terkirim'],
                            'belum_disurvei'   => ['label' => 'Belum',    'class' => 'badge-default'],
                        ];
                        $s = $statusMap[$alum->survey_status] ?? ['label' => $alum->survey_status, 'class' => 'badge-default'];
                    @endphp
                    <span class="badge {{ $s['class'] }}">{{ $s['label'] }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

</body>
</html>
