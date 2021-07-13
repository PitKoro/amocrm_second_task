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
                    <input class="form-control mb-2" type="text" name="name" id="name_input" placeholder="Name">
                    <input class="form-control mb-2" type="text" name="surname" id="surname_input" placeholder="Surname" >
                    <input class="form-control mb-2" type="number" name="age" id="age_input" placeholder="Age" >
                    <input class="form-control mb-2" type="tel" name="phone" id="phone_input" placeholder="Phone number" >
                    <input class="form-control mb-2" type="email" name="email" id="email_input" placeholder="Email" >
                    <p>Сhoose your gender:</p>
                    <div>
                        <input type="radio" id="gender_choice_male"
                        name="gender" value="male" checked>
                        <label for="gender_choice_male">Male</label>

                        <input type="radio" id="gender_choice_female"
                        name="gender" value="female">
                        <label for="gender_choice_female">Female</label>
                    </div>
                    <button class="btn btn-success mt-4" type="submit">Submit</button>
                </form>
            </div>
        </div>
    </div>
@endsection