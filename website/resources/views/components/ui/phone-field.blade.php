{{--
    Phone number, split into a dialling code and a national number.

    This was one free-text input with a "+65 8000 0000" placeholder, and it
    produced exactly the mess you would expect: sir's own sign-up went in as
    "91+6383515761". A placeholder cannot tell anyone what shape the answer
    should be — it disappears the moment they start typing — so people invent
    their own, and every invention lands in `users.phone`, which is unique and
    is what an employer eventually rings.

    So the code is a control rather than a hint. It defaults to +91: India is
    the lead market and `countries.sort_order` already puts it first. It is
    changeable, because Singapore and Malaysia are the other two markets, and
    the number beside it is only ever the local digits.

    The two fields are stitched back into a single `phone` on the server, in the
    form request's prepareForValidation(), so the controllers, the `phone`
    column and every existing rule are untouched — and so it still works with
    scripting off, which the rest of this wizard is careful about.

    No arbitrary Tailwind values here: this project's CSS bundle is prebuilt
    with no Node step, so a class no view was already using does not exist. The
    select's width is inline for that reason.
--}}
@props([
    'label' => 'Phone Number',
    'required' => false,
])

@php
    /*
        The three markets the platform serves (CLAUDE.md: SG, MY, IN). Kept
        here rather than in `countries` because that table carries no dialling
        code, and a two-row migration to hold three constants would be the
        larger change.
    */
    $dialCodes = [
        '+91' => 'India +91',
        '+65' => 'Singapore +65',
        '+60' => 'Malaysia +60',
    ];

    $selectedDial = old('dial_code', '+91');

    // The unique/required failures are reported against `phone`, the merged
    // field — the name of the box the person actually typed into is
    // `phone_national`, so both are checked or the message never appears.
    $error = $errors->first('phone') ?: $errors->first('phone_national');
@endphp

<div class="space-y-1">
    <label for="phone_national" class="form-label">
        {{ $label }}
        @if($required)
            <span class="text-danger ml-0.5">*</span>
        @endif
    </label>

    <div class="flex gap-2">
        <select name="dial_code"
                id="dial_code"
                aria-label="Country dialling code"
                class="form-input{{ $error ? ' error' : '' }}"
                style="width:9.5rem;flex:none;">
            @foreach($dialCodes as $code => $text)
                <option value="{{ $code }}" @selected($selectedDial === $code)>{{ $text }}</option>
            @endforeach
        </select>

        <input type="tel"
               name="phone_national"
               id="phone_national"
               inputmode="tel"
               autocomplete="tel-national"
               @if($required) required @endif
               value="{{ old('phone_national') }}"
               placeholder="9876543210"
               class="form-input{{ $error ? ' error' : '' }}"
               @if($error) aria-invalid="true" aria-describedby="phone-error" @endif>
    </div>

    @if($error)
        <p id="phone-error" class="form-error" role="alert">{{ $error }}</p>
    @else
        <p class="form-help">Just the local number — the code is set beside it.</p>
    @endif
</div>
