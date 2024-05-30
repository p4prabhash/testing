<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery Validation Plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
</head>

<style>
    .error {
        color: red;
    }
    ul, .error-messages {
        margin-top: 0;
        margin-bottom: 1rem;
        color: red;
    }
    .h2, h2 {
    font-size: 2rem;
    text-align: center;
}
</style>

<body>
    <div class="container mt-5">
        <h2>Test</h2>
        <div class="error-container">
            <ul class="error-messages"></ul>
        </div>
        <form id="userForm">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" class="form-control" id="phone" name="phone">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description"></textarea>
            </div>
            <div class="form-group">
                <label for="role_id">Role</label>
                <select class="form-control" id="role_id" name="role_id">
                    @foreach(App\Models\Role::all() as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="profile_image">Profile Image</label>
                <input type="file" class="form-control" id="profile_image" name="profile_image">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
        <table class="table mt-5">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Description</th>
                    <th>Role</th>
                    <th>Profile Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="userTable">
                <!-- Data will be populated here -->
            </tbody>
        </table>
    </div>

    <script>
        $("#userForm").validate({
            onfocusout: function(element) {
                $(element).valid();
            },
            highlight: function(element, errorClass) {},
            rules: {
                'name': {
                    required: true
                },
                'email': {
                    required: true,
                    email: true
                },
                'phone': {
                    required: true,
                    digits: true,
                    rangelength: [10, 10]
                },
                'description': {
                    required: true
                },
                'profile_image': {
                    required: true
                }
            },
            messages: {
                'name': "Please Enter name.",
                'email': "Please Enter email address.",
                'phone': {
                    required: "Please enter your mobile number.",
                    digits: "Please enter only digits.",
                    rangelength: "Please enter a valid 10-digit phone number."
                },
                'description': "Please Enter description.",
                'profile_image': "Please select profile image."
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            submitHandler: function(form) {
                // Ajax call to submit form data
            }
        });

        $(document).ready(function() {
            function fetchUsers() {
                $.ajax({
                    url: '/api/users',
                    method: 'GET',
                    success: function(data) {
                        $('#userTable').html('');
                        data.forEach(user => {
                            $('#userTable').append(`
                                <tr>
                                    <td>${user.name}</td>
                                    <td>${user.email}</td>
                                    <td>${user.phone}</td>
                                    <td>${user.description}</td>
                                    <td>${user.role.name}</td>
                                    <td><img src="<?php echo url('/');?>/${user.profile_image}" alt="${user.name}" width="50"></td>
                                    <td>
                                        <button class="btn btn-info btn-edit" data-id="${user.id}">Edit</button>
                                        <button class="btn btn-danger btn-delete" data-id="${user.id}">Delete</button>
                                    </td>
                                </tr>
                            `);
                        });
                    }
                });
            }

            fetchUsers();

            function displayErrors(errors) {
                let errorMessages = '';
                $.each(errors, function(key, value) {
                    errorMessages += `<li>${value}</li>`;
                });
                $('.error-messages').html(errorMessages);
            }

            $('#userForm').on('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                $.ajax({
                    url: '/api/users',
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        $('#userForm')[0].reset();
                        $('.error-messages').html('');
                        fetchUsers();
                    },
                    error: function(response) {
                        if (response.status === 422) {
                            displayErrors(response.responseJSON.errors);
                        }
                    }
                });
            });

            $(document).on('click', '.btn-delete', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: `/api/users/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        fetchUsers();
                    }
                });
            });

            $(document).on('click', '.btn-edit', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: `/api/users/${id}`,
                    method: 'GET',
                    success: function(user) {
                        $('#name').val(user.name);
                        $('#email').val(user.email);
                        $('#phone').val(user.phone);
                        $('#description').val(user.description);
                        $('#role_id').val(user.role_id);

                        $('#userForm').off('submit').on('submit', function(e) {
                            e.preventDefault();
                            let formData = new FormData(this);
                            $.ajax({
                                url: `/api/users/${id}`,
                                method: 'POST',
                                data: formData,
                                contentType: false,
                                processData: false,
                                success: function(response) {
                                    $('#userForm')[0].reset();
                                    $('.error-messages').html('');
                                    fetchUsers();

                                    $('#userForm').off('submit').on('submit', function(e) {
                                        e.preventDefault();
                                        let formData = new FormData(this);
                                        $.ajax({
                                            url: '/api/users',
                                            method: 'POST',
                                            data: formData,
                                            contentType: false,
                                            processData: false,
                                            success: function(response) {
                                                $('#userForm')[0].reset();
                                                $('.error-messages').html('');
                                                fetchUsers();
                                            },
                                            error: function(response) {
                                                if (response.status === 422) {
                                                    displayErrors(response.responseJSON.errors);
                                                }
                                            }
                                        });
                                    });
                                },
                                error: function(response) {
                                    if (response.status === 422) {
                                        displayErrors(response.responseJSON.errors);
                                    }
                                }
                            });
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>
