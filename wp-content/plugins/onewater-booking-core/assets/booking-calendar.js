(function () {
  const roots = document.querySelectorAll('[data-onewater-booking]');
  if (!roots.length || !window.oneWaterBooking) return;

  const apiBase = window.oneWaterBooking.apiBase;
  const monthFormatter = new Intl.DateTimeFormat('en-CA', { month: 'long', year: 'numeric' });
  const dateFormatter = new Intl.DateTimeFormat('en-CA', { month: 'short', day: 'numeric', year: 'numeric' });

  function isoDate(date) {
    return date.toISOString().slice(0, 10);
  }

  function monthKey(date) {
    return date.toISOString().slice(0, 7);
  }

  async function fetchAvailability(date) {
    const response = await fetch(`${apiBase}/availability?month=${monthKey(date)}`);
    if (!response.ok) throw new Error('Could not load availability.');
    return response.json();
  }

  function render(root, currentMonth, selectedDate, availability) {
    const grid = root.querySelector('[data-calendar-grid]');
    const label = root.querySelector('[data-calendar-label]');
    const selection = root.querySelector('[data-calendar-selection]');
    const startInput = root.querySelector('[data-start-date]');
    const firstDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
    const daysByDate = new Map(availability.days.map((day) => [day.date, day]));

    label.textContent = monthFormatter.format(currentMonth);
    grid.innerHTML = '';

    for (let index = 0; index < firstDay.getDay(); index += 1) {
      const spacer = document.createElement('span');
      spacer.className = 'ows-booking__spacer';
      grid.appendChild(spacer);
    }

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

      if (selectedDate === day.date) {
        button.classList.add('is-selected');
      }

      button.addEventListener('click', () => {
        const selected = daysByDate.get(day.date);
        startInput.value = selected.date;
        selection.textContent = `${dateFormatter.format(new Date(`${selected.date}T00:00:00`))} to ${dateFormatter.format(new Date(`${selected.checkout_date}T00:00:00`))}`;
        render(root, currentMonth, selected.date, availability);
      });

      grid.appendChild(button);
    });
  }

  roots.forEach((root) => {
    let currentMonth = new Date();
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);

    const result = root.querySelector('[data-booking-result]');
    const form = root.querySelector('[data-booking-form]');
    const previous = root.querySelector('[data-calendar-prev]');
    const next = root.querySelector('[data-calendar-next]');

    async function refresh(selectedDate = '') {
      result.textContent = 'Loading availability...';
      try {
        const availability = await fetchAvailability(currentMonth);
        render(root, currentMonth, selectedDate, availability);
        result.textContent = '';
      } catch (error) {
        result.textContent = error.message;
      }
    }

    previous.addEventListener('click', () => {
      currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
      refresh();
    });

    next.addEventListener('click', () => {
      currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
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
        result.textContent = payload.message;
        form.reset();
        refresh();
      } catch (error) {
        result.textContent = error.message;
      }
    });

    refresh();
  });
})();
