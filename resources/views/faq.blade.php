@extends('layouts.app')

@section('title', 'Frequently Asked Questions - COPRRA')

@section('content')
<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-center">Frequently Asked Questions (FAQ)</h1>

        <div class="space-y-4">
            <!-- Question 1 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 cursor-pointer hover:bg-gray-50" onclick="toggleAccordion('q1')">
                    <h2 class="text-xl font-semibold flex justify-between items-center">
                        How does COPRRA make money?
                        <span id="q1-icon">▼</span>
                    </h2>
                </div>
                <div id="q1" class="hidden">
                    <div class="p-6 bg-gray-50 border-t">
                        <p>COPRRA is free for users. We earn a commission through affiliate marketing when you click a link and make a purchase, at no extra cost to you.</p>
                    </div>
                </div>
            </div>

            <!-- Question 2 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 cursor-pointer hover:bg-gray-50" onclick="toggleAccordion('q2')">
                    <h2 class="text-xl font-semibold flex justify-between items-center">
                        Are your comparisons biased?
                        <span id="q2-icon">▼</span>
                    </h2>
                </div>
                <div id="q2" class="hidden">
                    <div class="p-6 bg-gray-50 border-t">
                        <p>No. Our recommendations are based on data and expert reviews, not on commission rates.</p>
                    </div>
                </div>
            </div>

            <!-- Question 3 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 cursor-pointer hover:bg-gray-50" onclick="toggleAccordion('q3')">
                    <h2 class="text-xl font-semibold flex justify-between items-center">
                        How often is product information updated?
                        <span id="q3-icon">▼</span>
                    </h2>
                </div>
                <div id="q3" class="hidden">
                    <div class="p-6 bg-gray-50 border-t">
                        <p>We update data frequently, but always check the final price on the retailer's website before purchasing.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce', '') }}">
function toggleAccordion(id) {
    const content = document.getElementById(id);
    const icon = document.getElementById(id + '-icon');

    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.textContent = '▲';
    } else {
        content.classList.add('hidden');
        icon.textContent = '▼';
    }
}
</script>
@endsection
