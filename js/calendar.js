document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  var calendar = new FullCalendar.Calendar(calendarEl, {
    headerToolbar: { center: 'dayGridMonth,listMonth' },
    initialView: 'dayGridMonth',
    weekNumbers: true,
    locale: 'de',
    events: {
      url: window.location+'.json',
      method: 'GET',
      failure: function() {
        alert('Fehler beim Laden.');
      },
    },
    eventClick: function (info) {
      info.jsEvent.preventDefault();
      var props = info.event.extendedProps;
      console.log(info);
      var content = `Anlass in einem neuen Tab ansehen? \n\n${info.event.title}\n${props.duration}`;
      if (props.parent) {
        content += `\n\n${props.parent}`;
      }
      var r = confirm(content);
      if (r == true) {
        window.open(info.event.url);
      }
    }
  });
  calendar.render();
});