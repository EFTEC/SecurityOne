<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">


    <title>Floating labels example for Bootstrap</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">

    <!-- Custom styles for this template -->
    <link href="css/floating.css" rel="stylesheet">
</head>

<body>
<form class="form-signin" method="post">


    <div class="form-label-group">
        <input type="text" id="inputEmail" name="user" class="form-control" placeholder="user" required autofocus value="{{$user}}">
        <label for="inputEmail">User</label>
    </div>

    <div class="form-label-group">
        <input type="password" id="inputPassword" name="password" class="form-control" placeholder="Password" required value="{{$password}}">
        <label for="inputPassword">Password</label>
    </div>

    <div class="checkbox mb-3">
        <label>
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>
    </div>
    <button class="btn btn-lg btn-primary btn-block" name="button" value="1" type="submit">Sign in</button>
    <p>{{$message}}</p>
    <p class="mt-5 mb-3 text-muted text-center">&copy; 2017-2018</p>

</form>
</body>
</html>
