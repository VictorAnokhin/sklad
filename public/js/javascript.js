
const phone = document.querySelector('.phone2');
if (phone) {
    phone.addEventListener('input', function () {
        this.value = '4';
        if (phone.value != '') {
            phone.value = phone.value + 2;
        }
    });
}

/*
filter.onclick = function() {myFunctionButton()};

function myFunctionButton() {
  const divfilter = document.getElementById('divfilter');

  if (divfilter.style.display == 'none'){
    divfilter.style.display = 'block';
  }
  else {
    divfilter.style.display = 'none';
  }
}
*/

function confirmDelete() {
    if (confirm("Вы подтверждаете удаление?")) {
        return true;
    } else {
        return false;
    }
}

const menubtn = document.querySelector('.menu_btn');
const content = document.querySelector('.menu_content');
menubtn.addEventListener('click', function () {

    if (content.style.display == 'block') {
        content.style.display = 'none';
        menubtn.classList.remove('active');
    }
    else {
        content.style.display = 'block';
        menubtn.classList.add('active');
    }
})

window.onload = function () {
    document.body.classList.add('loaded_hiding');
    window.setTimeout(function () {
        document.body.classList.add('loaded');
        document.body.classList.remove('loaded_hiding');
    }, 1500);
}
