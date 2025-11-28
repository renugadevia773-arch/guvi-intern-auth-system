// jquery ajax logic here
// js/profile.js
$(function(){
  const token = localStorage.getItem('session_token');
  if (!token) {
    location.href = 'login.html';
    return;
  }

  function showAlert(html, type='success') {
    $('#alert').html('<div class="alert alert-' + type + '">' + html + '</div>');
    setTimeout(()=> $('#alert').html(''), 3500);
  }

  // load profile (action=get)
  $.ajax({
    url: 'php/profile.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({ action: 'get', token }),
    success: function(resp){
      if (!resp.success) {
        showAlert(resp.error || 'Error loading profile', 'danger');
        if (resp.error && resp.error.toLowerCase().includes('session')) {
          localStorage.removeItem('session_token');
          setTimeout(()=> location.href = 'login.html', 1000);
        }
        return;
      }
      const p = resp.profile || {};
      $('#name').val(p.name || localStorage.getItem('user_name') || '');
      $('#age').val(p.age || '');
      $('#dob').val(p.dob ? (p.dob.split('T')[0] || p.dob) : '');
      $('#contact').val(p.contact || '');
    },
    error: function(){ showAlert('Server error', 'danger'); }
  });

  // save profile (action=update)
  $('#saveProfile').on('click', function(){
    const payload = {
      action: 'update',
      token,
      name: $('#name').val().trim(),
      age: $('#age').val(),
      dob: $('#dob').val(),
      contact: $('#contact').val()
    };
    $('#saveProfile').prop('disabled', true);
    $.ajax({
      url: 'php/profile.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload),
      success: function(resp){
        if (resp.success) showAlert('Profile saved.');
        else showAlert(resp.error || 'Save failed', 'danger');
      },
      error: function(){ showAlert('Server error','danger'); },
      complete: function(){ $('#saveProfile').prop('disabled', false); }
    });
  });

  // logout
  $('#logoutBtn').on('click', function(){
    $.ajax({
      url: 'php/profile.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ action: 'logout', token }),
      success: function(){ localStorage.removeItem('session_token'); localStorage.removeItem('user_name'); location.href = 'login.html'; },
      error: function(){ localStorage.removeItem('session_token'); location.href = 'login.html'; }
    });
  });
});
