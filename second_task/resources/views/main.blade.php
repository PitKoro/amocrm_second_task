@extends('base')

@section('content')
    <div class="container mt-5">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="row">
            <div class="col">
                <form action="/validation" method="post">
                    @csrf
                    <input class="form-control mb-2 js-validation-name-fields" type="text" name="name" id="name_input" placeholder="Name" required>
                    <input class="form-control mb-2 js-validation-surname-fields" type="text" name="surname" id="surname_input" placeholder="Surname" required>
                    <input class="form-control mb-2 js-validation-age-fields" type="text" name="age" maxlength="3" id="age_input" placeholder="Age" required>
                    <input class="form-control mb-2 js-validation-phone-fields" type="tel" name="phone" maxlength="11" id="phone_input" placeholder="Phone number" required>
                    <input class="form-control mb-2 js-validation-email-fields" type="email" name="email" id="email_input" placeholder="Email" required>
                    <p>Сhoose your gender:</p>
                    <div>
                        <input type="radio" id="gender_choice_male"
                        name="gender" value="мужской" checked>
                        <label for="gender_choice_male">Male</label>

                        <input type="radio" id="gender_choice_female"
                        name="gender" value="женский">
                        <label for="gender_choice_female">Female</label>
                    </div>
                    <button class="btn btn-success mt-4 js-button-submit" type="submit">Submit</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script
            src="https://code.jquery.com/jquery-3.6.0.js"
            integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk="
            crossorigin="anonymous"></script>
    <script src="../js/app.js"></script>
@endsection