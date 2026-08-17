@props(['faqs' => []])

<section class="bg-white" aria-labelledby="faq-title">
    <div class="site-container site-section">
        <p class="site-eyebrow">Câu hỏi thường gặp</p>
        <h2 id="faq-title" class="site-section-title">Giải đáp thắc mắc về tra cứu phạt nguội</h2>

        <div class="mt-5 grid items-start gap-2.5 md:grid-cols-2">
            @foreach ($faqs as $faq)
                <details class="group rounded-lg border border-slate-200 bg-white open:border-sky-200 open:bg-sky-50/40">
                    <summary class="site-focus flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 rounded-lg px-3 py-2 text-xs font-bold leading-5 text-navy [&::-webkit-details-marker]:hidden sm:text-sm">
                        <span>{{ $faq['question'] }}</span>
                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180"><path d="m5 7.5 5 5 5-5"/></svg>
                    </summary>
                    <p class="px-3 pb-3 text-xs leading-6 text-slate-600">{{ $faq['answer'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
