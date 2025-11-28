// jquery ajax logic here
// js/register.js
$(function(){
  $('#registerBtn').on('click', function(){
    $('#alert').html('');
    const payload = {
      name: $('#name').val().trim(),
      email: $('#email').val().trim(),
      password: $('#password').val()
    };
    if (!payload.name || !payload.email || !payload.password) {
      $('#alert').html('<div class="alert alert-danger">All fields are required.</div>');
      return;
    }
    $('#registerBtn').prop('disabled', true);
    $.ajax({
      url: 'php/register.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload),
      success: function(resp){
        if (resp.success) {
          $('#alert').html('<div class="alert alert-success">Registered successfully. Redirecting to login...</div>');
          setTimeout(()=> location.href = 'login.html', 1200);
        } else {
          $('#alert').html('<div class="alert alert-danger">'+(resp.error||'Registration failed')+'</div>');
        }
      },
      error: function(){ $('#alert').html('<div class="alert alert-danger">Server error</div>'); },
      complete: function(){ $('#registerBtn').prop('disabled', false); }
    });
  });
});
