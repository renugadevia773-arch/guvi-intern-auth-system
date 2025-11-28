// jquery ajax logic here
// js/login.js
$(function(){
  $('#loginBtn').on('click', function(){
    $('#alert').html('');
    const payload = {
      email: $('#email').val().trim(),
      password: $('#password').val()
    };
    if (!payload.email || !payload.password) {
      $('#alert').html('<div class="alert alert-danger">Both email and password required.</div>');
      return;
    }
    $('#loginBtn').prop('disabled', true);
    $.ajax({
      url: 'php/login.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload),
      success: function(resp){
        if (resp.success) {
          localStorage.setItem('session_token', resp.token);
          localStorage.setItem('user_name', resp.user.name || '');
          location.href = 'profile.html';
        } else {
          $('#alert').html('<div class="alert alert-danger">'+(resp.error||'Login failed')+'</div>');
        }
      },
      error: function(){ $('#alert').html('<div class="alert alert-danger">Server error</div>'); },
      complete: function(){ $('#loginBtn').prop('disabled', false); }
    });
  });
});
