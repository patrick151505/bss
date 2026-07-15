{{--
    Certificate Paper Partial
    Variables expected:
      $documentRequest  — the DocumentRequest model (with documentType.template + citizen loaded)
      $header           — rendered header HTML (from renderHeader())
      $preview          — rendered body HTML (from resolveTemplate())
      $forPrint         — bool, true = no border/shadow (for print page)
--}}
@php
    $forPrint  = $forPrint ?? false;

    // Use the version locked at request creation time — immune to later template changes
    $ver       = $documentRequest->templateVersion;
    $hasBg     = $ver && $ver->paper_bg;
    $bgUrl     = $hasBg ? asset('storage/' . $ver->paper_bg) : null;
    $padTop    = $ver?->padding_top    ?? 48;
    $padBottom = $ver?->padding_bottom ?? 72;
    $padLeft   = $ver?->padding_left   ?? 72;
    $padRight  = $ver?->padding_right  ?? 72;
@endphp

<div id="certificate-paper"
     class="{{ $forPrint ? '' : 'border border-gray-200 dark:border-gray-700 shadow-sm' }} bg-white rounded-lg relative overflow-hidden"
     style="
        width: 100%;
        min-height: 842px;
        position: relative;
        font-family: 'Times New Roman', Times, serif;
        font-size: 13px;
        line-height: 1.8;
        color: #1a1a1a;
        {{ $hasBg ? 'background-image: url(' . $bgUrl . '); background-size: cover; background-position: top center; background-repeat: no-repeat;' : '' }}
     ">

    {{-- Watermark overlay (faint logo when no background image) --}}
    @if(!$hasBg)
    @php $settings = \App\Models\Setting::instance(); @endphp
    @if($settings->logo)
    <div style="
        position: absolute; top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 380px; height: 380px;
        background-image: url('{{ asset('storage/' . $settings->logo) }}');
        background-size: contain; background-repeat: no-repeat; background-position: center;
        opacity: 0.06;
        pointer-events: none;
        z-index: 0;
    "></div>
    @endif
    @endif

    {{-- Content area —padded per template settings --}}
    <div style="position: relative; z-index: 1; padding: {{ $padTop }}px {{ $padRight }}px {{ $padBottom }}px {{ $padLeft }}px;">

        @if(!$hasBg)
        {{-- Auto-built header (only shown when no paper background image) --}}
        <div style="margin-bottom: 28px;">
            {!! $header !!}
        </div>
        @endif

        {{-- Certificate body --}}
        <div class="ql-editor" style="padding: 0;">
            {!! $preview !!}
        </div>

    </div>
</div>
