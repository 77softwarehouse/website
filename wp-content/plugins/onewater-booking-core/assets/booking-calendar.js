(function () {
  const roots = document.querySelectorAll('[data-onewater-booking]');
  if (!roots.length || !window.oneWaterBooking) return;

  const apiBase = window.oneWaterBooking.apiBase;
  const MONTHS_SHOWN = 3;
  const rangeFormatter = new Intl.DateTimeFormat('en-CA', { month: 'long', year: 'numeric' });
  const monthFormatter = new Intl.DateTimeFormat('en-CA', { month: 'long' });
  const dateFormatter = new Intl.DateTimeFormat('en-CA', { month: 'short', day: 'numeric', year: 'numeric' });
  const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

  function monthKey(date) {
    return date.toISOString().slice(0, 7);
  }

  function addMonths(date, amount) {
    return new Date(date.getFullYear(), date.getMonth() + amount, 1);
  }

  // The checkout date is the move-out day (start + 3 months). The last occupied
  // night is the day before, so a stay starting on the 1st ends on the last day
  // of the third month.
  function lastNight(checkoutIso) {
    const date = new Date(`${checkoutIso}T00:00:00`);
    date.setDate(date.getDate() - 1);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  async function fetchAvailability(date) {
    const response = await fetch(`${apiBase}/availability?month=${monthKey(date)}`);
    if (!response.ok) throw new Error('Could not load availability.');
    return response.json();
  }

  function buildMonthPanel(monthDate, availability, selectedRange, onSelect) {
    const panel = document.createElement('div');
    panel.className = 'ows-booking__month';

    const title = document.createElement('div');
    title.className = 'ows-booking__month-title';
    title.textContent = monthFormatter.format(monthDate);
    panel.appendChild(title);

    const weekdayRow = document.createElement('div');
    weekdayRow.className = 'ows-booking__weekdays';
    weekdayRow.setAttribute('aria-hidden', 'true');
    weekdays.forEach((label) => {
      const span = document.createElement('span');
      span.textContent = label;
      weekdayRow.appendChild(span);
    });
    panel.appendChild(weekdayRow);

    const grid = document.createElement('div');
    grid.className = 'ows-booking__calendar';

    const firstDay = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1);
    for (let index = 0; index < firstDay.getDay(); index += 1) {
      const spacer = document.createElement('span');
      spacer.className = 'ows-booking__spacer';
      grid.appendChild(spacer);
    }

    const daysByDate = new Map(availability.days.map((day) => [day.date, day]));
    availability.days.forEach((day) => {
      const button = document.createElement('button');
      const date = new Date(`${day.date}T00:00:00`);
      button.type = 'button';
      button.className = 'ows-booking__day';
      button.textContent = String(date.getDate());
      button.dataset.date = day.date;
      button.dataset.checkout = day.checkout_date;
      button.title = day.available ? 'Available exact 3-month start date' : `Unavailable: ${day.reason}`;
      button.disabled = !day.available;

      if (selectedRange) {
        if (day.date === selectedRange.start) {
          button.classList.add('is-range-start');
          button.setAttribute('aria-current', 'date');
        } else if (day.date === selectedRange.end) {
          button.classList.add('is-range-end');
        } else if (day.date > selectedRange.start && day.date < selectedRange.end) {
          button.classList.add('is-in-range');
        }
      }

      button.addEventListener('click', () => onSelect(daysByDate.get(day.date)));
      grid.appendChild(button);
    });

    panel.appendChild(grid);
    return panel;
  }

  function render(root, baseMonth, selectedRange, availabilityList, onSelect) {
    const monthsEl = root.querySelector('[data-calendar-months]');
    const label = root.querySelector('[data-calendar-label]');
    const monthDates = availabilityList.map((_, index) => addMonths(baseMonth, index));

    const firstLabel = rangeFormatter.format(monthDates[0]);
    const lastLabel = rangeFormatter.format(monthDates[monthDates.length - 1]);
    label.textContent = monthDates.length > 1 ? `${firstLabel} – ${lastLabel}` : firstLabel;

    monthsEl.innerHTML = '';
    availabilityList.forEach((availability, index) => {
      monthsEl.appendChild(buildMonthPanel(monthDates[index], availability, selectedRange, onSelect));
    });
  }

  roots.forEach((root) => {
    let currentMonth = new Date();
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);

    const result = root.querySelector('[data-booking-result]');
    const form = root.querySelector('[data-booking-form]');
    const previous = root.querySelector('[data-calendar-prev]');
    const next = root.querySelector('[data-calendar-next]');
    const selection = root.querySelector('[data-calendar-selection]');
    const startInput = root.querySelector('[data-start-date]');

    let selectedRange = null;
    let availabilityList = [];

    function draw() {
      render(root, currentMonth, selectedRange, availabilityList, handleSelect);
    }

    function handleSelect(selected) {
      if (!selected) return;
      const endDate = lastNight(selected.checkout_date);
      selectedRange = { start: selected.date, end: endDate };
      startInput.value = selected.date;
      selection.textContent = `${dateFormatter.format(new Date(`${selected.date}T00:00:00`))} to ${dateFormatter.format(new Date(`${endDate}T00:00:00`))}`;
      draw();
    }

    async function refresh() {
      result.textContent = 'Loading availability...';
      try {
        const months = Array.from({ length: MONTHS_SHOWN }, (_, index) => addMonths(currentMonth, index));
        availabilityList = await Promise.all(months.map(fetchAvailability));
        draw();
        result.textContent = '';
      } catch (error) {
        result.textContent = error.message;
      }
    }

    previous.addEventListener('click', () => {
      currentMonth = addMonths(currentMonth, -1);
      refresh();
    });

    next.addEventListener('click', () => {
      currentMonth = addMonths(currentMonth, 1);
      refresh();
    });

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(form);

      if (!formData.get('start_date')) {
        result.textContent = 'Please select an available start date first.';
        return;
      }

      result.textContent = 'Submitting request...';
      try {
        const response = await fetch(`${apiBase}/reservations`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.oneWaterBooking.nonce,
          },
          body: JSON.stringify(Object.fromEntries(formData.entries())),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Booking request failed.');
        result.innerHTML = '';
        const message = document.createElement('span');
        message.textContent = payload.message;
        result.appendChild(message);
        const link = document.createElement('a');
        link.href = '/my-bookings';
        link.className = 'ows-booking__mybookings-link';
        link.textContent = 'View in My Bookings';
        result.appendChild(document.createElement('br'));
        result.appendChild(link);
        form.reset();
        selectedRange = null;
        selection.textContent = 'Choose a start date to see the exact 3-month period.';
        refresh();
      } catch (error) {
        result.textContent = error.message;
      }
    });

    refresh();
  });
})();
