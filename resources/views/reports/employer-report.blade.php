<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Survei Employer</title>
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
            background-color: #00537a;
            margin: 0 auto 20px auto;
            display: block;
        }

        .cover-university {
            font-size: 13pt;
            font-weight: bold;
            color: #00537a;
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
            background-color: #00537a;
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

        .cover-meta {
            font-size: 9pt;
            color: #888888;
            margin-top: 60px;
        }

        /* ===== GENERAL LAYOUT ===== */
        .page-header {
            border-bottom: 2px solid #00537a;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        .page-header h1 {
            font-size: 11pt;
            color: #00537a;
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
            color: #00537a;
            border-left: 3px solid #00537a;
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
            padding: 10px 8px;
            text-align: center;
            border: 1px solid #e0e0e0;
            background-color: #f0f7fa;
        }

        .summary-value {
            font-size: 16pt;
            font-weight: bold;
            color: #00537a;
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
            background-color: #00537a;
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
            background-color: #f0f7fa;
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

        .badge-selesai  { background-color: #d4edda; color: #155724; }
        .badge-terkirim { background-color: #cce5ff; color: #004085; }
        .badge-default  { background-color: #e2e3e5; color: #383d41; }

        .text-muted  { color: #888888; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }

        /* ===== COMPANY TYPE LABELS ===== */
        .type-label {
            font-size: 8pt;
            text-transform: capitalize;
        }
    </style>
</head>
<body>

{{-- ===== FOOTER (fixed, muncul di semua halaman) ===== --}}
<div class="footer">
    {{ $university_name }} &nbsp;|&nbsp; Laporan Survei Employer &nbsp;|&nbsp;
    Digenerate: {{ \Carbon\Carbon::parse($generated_at)->format('d/m/Y H:i') }} WITA
</div>

{{-- ===== HALAMAN COVER ===== --}}
<div class="cover">
    <div class="cover-logo-placeholder"></div>
    <div class="cover-university">{{ $university_name }}</div>
    <div class="cover-tagline">{{ $university_tagline }}</div>
    <div class="cover-divider"></div>
    <div class="cover-title">Laporan Survei</div>
    <div class="cover-title">Pengguna Lulusan</div>
    <div class="cover-subtitle" style="margin-top: 16px;">(Employer / Mitra Industri)</div>
    <div class="cover-meta">
        Digenerate pada:
        {{ \Carbon\Carbon::parse($generated_at)->setTimezone('Asia/Makassar')->format('d F Y, H:i') }} WITA
    </div>
</div>

{{-- ===== HALAMAN KONTEN ===== --}}
<div class="page-header">
    <h1>Laporan Survei Pengguna Lulusan (Employer) &mdash; {{ $university_name }}</h1>
</div>

{{-- Section 1: Ringkasan --}}
<div class="section">
    <div class="section-title">1. Ringkasan</div>

    @php
        $totalEmployers   = $employers->count();
        $byType           = $employers->groupBy('company_type');
        $bySector         = $employers->groupBy('industry_sector');
        $byScale          = $employers->groupBy('company_scale');
    @endphp

    <table class="summary-grid">
        <tr>
            <td style="width: 25%;">
                <span class="summary-value">{{ number_format($totalEmployers) }}</span>
                <span class="summary-label">Total Employer (Survei Selesai)</span>
            </td>
            <td style="width: 25%;">
                <span class="summary-value">{{ $byType->count() }}</span>
                <span class="summary-label">Jenis Perusahaan</span>
            </td>
            <td style="width: 25%;">
                <span class="summary-value">{{ $bySector->count() }}</span>
                <span class="summary-label">Sektor Industri</span>
            </td>
            <td style="width: 25%;">
                <span class="summary-value">{{ $byScale->count() }}</span>
                <span class="summary-label">Skala Perusahaan</span>
            </td>
        </tr>
    </table>
</div>

{{-- Section 2: Distribusi per Jenis Perusahaan --}}
@if($byType->count() > 0)
<div class="section">
    <div class="section-title">2. Distribusi per Jenis Perusahaan</div>
    @php
        $typeLabels = [
            'swasta'      => 'Swasta',
            'pemerintah'  => 'Pemerintah / BUMN',
            'pendidikan'  => 'Institusi Pendidikan',
            'lainnya'     => 'Lainnya',
        ];
    @endphp
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th>Jenis Perusahaan</th>
                <th class="text-right" style="width: 15%;">Jumlah</th>
                <th class="text-right" style="width: 15%;">&nbsp;%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byType as $type => $group)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ $typeLabels[$type] ?? ucfirst($type) }}</td>
                <td class="text-right">{{ $group->count() }}</td>
                <td class="text-right">
                    {{ $totalEmployers > 0 ? number_format(($group->count() / $totalEmployers) * 100, 1) : '0.0' }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Section 3: Distribusi per Sektor Industri --}}
@if($bySector->count() > 0)
<div class="section">
    <div class="section-title">3. Distribusi per Sektor Industri</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th>Sektor Industri</th>
                <th class="text-right" style="width: 15%;">Jumlah</th>
                <th class="text-right" style="width: 15%;">&nbsp;%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bySector->sortByDesc(fn($g) => $g->count()) as $sector => $group)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ $sector ?: '<em class="text-muted">Tidak disebutkan</em>' }}</td>
                <td class="text-right">{{ $group->count() }}</td>
                <td class="text-right">
                    {{ $totalEmployers > 0 ? number_format(($group->count() / $totalEmployers) * 100, 1) : '0.0' }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Section 4: Daftar Lengkap Employer --}}
@if($employers->count() > 0)
<div class="section page-break">
    <div class="page-header" style="margin-top: 0;">
        <h1>Laporan Survei Pengguna Lulusan (Employer) &mdash; {{ $university_name }}</h1>
    </div>
    <div class="section-title">4. Daftar Employer yang Telah Mengisi Survei</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th>Nama Perusahaan</th>
                <th style="width: 14%;">Jenis</th>
                <th style="width: 14%;">Skala</th>
                <th style="width: 18%;">Sektor Industri</th>
                <th style="width: 14%;">Kota</th>
                <th class="text-center" style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employers as $i => $employer)
            @php
                $scaleMap = [
                    'kecil'    => 'Kecil',
                    'menengah' => 'Menengah',
                    'besar'    => 'Besar',
                ];
            @endphp
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $employer->company_name }}</td>
                <td class="type-label">{{ $typeLabels[$employer->company_type] ?? ucfirst($employer->company_type ?? '-') }}</td>
                <td>{{ $scaleMap[$employer->company_scale] ?? ucfirst($employer->company_scale ?? '-') }}</td>
                <td>{{ $employer->industry_sector ?? '-' }}</td>
                <td>{{ $employer->address_city ?? '-' }}</td>
                <td class="text-center">
                    @if($employer->survey_status === 'selesai')
                        <span class="badge badge-selesai">Selesai</span>
                    @elseif($employer->survey_status === 'terkirim')
                        <span class="badge badge-terkirim">Terkirim</span>
                    @else
                        <span class="badge badge-default">{{ ucfirst($employer->survey_status ?? '-') }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

</body>
</html>
