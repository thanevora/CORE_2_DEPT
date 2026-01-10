document.addEventListener("DOMContentLoaded", () => {
  initIcons();
  initForm();
  initCalendar();
  initReservationModal();
  initTableDropdown();
});

/* -------------------- ICONS -------------------- */
function initIcons() {
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }
}

/* -------------------- RESERVATION FORM -------------------- */
function initForm() {
  const form = document.getElementById("reservation-form");
  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i> Processing...';
    submitBtn.disabled = true;
    
    try {
      const formData = new FormData(form);
      const res = await fetch(form.action, { method: "POST", body: formData });
      
      // Check if response is JSON
      const contentType = res.headers.get("content-type");
      if (contentType && contentType.includes("application/json")) {
        const data = await res.json();

        if (data.success) {
          document.getElementById("reservations-modal")?.close();

          Swal.fire({
            title: "Success!",
            text: data.message || "Reservation added successfully",
            icon: "success",
            timer: 2000,
            showConfirmButton: false,
            timerProgressBar: true,
          }).then(() => window.location.reload());
        } else {
          // Handle validation errors from PHP
          let errorMessage = data.message || "Failed to add reservation";
          
          // If there are specific errors, display them
          if (data.errors && data.errors.length > 0) {
            errorMessage += "<br><br>" + data.errors.join("<br>");
          }
          
          Swal.fire({
            title: "Error!",
            html: errorMessage,
            icon: "error",
            confirmButtonColor: "#ef4444",
          });
        }
      } else {
        // Handle non-JSON response (like redirects)
        const text = await res.text();
        console.warn("Non-JSON response received:", text);
        window.location.reload();
      }
    } catch (error) {
      console.error("Submission error:", error);
      Swal.fire({
        title: "Error!",
        text: "An error occurred while adding the reservation",
        icon: "error",
        confirmButtonColor: "#ef4444",
      });
    } finally {
      // Restore button state
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
      if (typeof lucide !== "undefined") {
        lucide.createIcons();
      }
    }
  });
}

/* -------------------- CALENDAR -------------------- */
let currentDate = new Date();

function initCalendar() {
  renderCalendar(currentDate);

  const prevBtn = document.getElementById("prev-month");
  const nextBtn = document.getElementById("next-month");
  const todayBtn = document.getElementById("today");

  prevBtn?.addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar(currentDate);
  });

  nextBtn?.addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar(currentDate);
  });

  todayBtn?.addEventListener("click", () => {
    currentDate = new Date();
    renderCalendar(currentDate);
  });
}

function renderCalendar(date) {
  const calendarGrid = document.getElementById("calendar-grid");
  const monthYear = document.getElementById("month-year");
  if (!calendarGrid || !monthYear) return;

  const year = date.getFullYear();
  const month = date.getMonth();
  const firstDay = new Date(year, month, 1).getDay();
  const lastDate = new Date(year, month + 1, 0).getDate();

  monthYear.textContent = `${date.toLocaleString("default", { month: "long" })} ${year}`;
  calendarGrid.innerHTML = "";

  // Fill empty slots before the first day
  for (let i = 0; i < firstDay; i++) {
    calendarGrid.innerHTML += `<div class="bg-white h-20"></div>`;
  }

  // Render days
  for (let day = 1; day <= lastDate; day++) {
    const fullDate = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;

    calendarGrid.innerHTML += `
      <div class="bg-white h-20 hover:bg-blue-100 cursor-pointer text-center p-2"
           onclick="loadReservations('${fullDate}')">
        <div class="text-sm font-bold">${day}</div>
      </div>`;
  }
}

/* -------------------- RESERVATION MODAL -------------------- */
function initReservationModal() {
  const modal = document.getElementById("reservations-modal"); // Fixed ID
  const closeBtn = document.getElementById("close-modal"); // Fixed selector

  if (!modal || !closeBtn) return;

  closeBtn.addEventListener("click", () => modal.close());
  document.addEventListener("keydown", (e) => e.key === "Escape" && modal.close());
  
  // Close modal when clicking outside
  modal.addEventListener("click", (e) => {
    if (e.target === modal) modal.close();
  });
}

/* -------------------- FETCH RESERVATIONS -------------------- */
window.loadReservations = (dateStr) => {
  const modal = document.getElementById("reservations-modal"); // Fixed ID
  const modalDate = document.getElementById("modal-date-title"); // Fixed ID
  const reservationsList = document.getElementById("reservations-list");
  
  if (!modal || !modalDate || !reservationsList) return;

  modalDate.textContent = `Reservations for ${new Date(dateStr).toDateString()}`;
  reservationsList.innerHTML = `<tr><td colspan="6" class="text-center">Loading...</td></tr>`;

  // Show the modal
  modal.showModal();

  fetch(`../M1/fetch_reservation.php?date=${dateStr}`)
    .then((res) => res.json())
    .then((data) => {
      reservationsList.innerHTML = "";

      if (!data.length) {
        reservationsList.innerHTML = `<tr><td colspan="6" class="text-center text-gray-500">No reservations for this date</td></tr>`;
        return;
      }

      data.forEach((res) => {
        // Determine badge color based on status
        let badgeClass = "badge-outline ";
        switch(res.status) {
          case "Confirmed":
            badgeClass += "badge-success";
            break;
          case "Pending":
            badgeClass += "badge-warning";
            break;
          case "Cancelled":
            badgeClass += "badge-error";
            break;
          case "Completed":
            badgeClass += "badge-info";
            break;
          default:
            badgeClass += "badge-outline";
        }

        reservationsList.innerHTML += `
          <tr>
            <td class="font-medium">${res.name}</td>
            <td>${res.contact}</td>
            <td>${res.start_time} - ${res.end_time}</td>
            <td>${res.size}</td>
            <td>${res.type}</td>
            <td><span class="badge ${badgeClass}">${res.status || "Pending"}</span></td>
          </tr>`;
      });
    })
    .catch((error) => {
      console.error("Error loading reservations:", error);
      reservationsList.innerHTML = `<tr><td colspan="6" class="text-center text-red-500">Error loading reservations</td></tr>`;
    });
};

/* -------------------- TABLE DROPDOWN -------------------- */
function initTableDropdown() {
  const select = document.getElementById("table_id");
  if (!select) return;

  // Add event listener to update the dropdown arrow color based on selected table status
  select.addEventListener("change", function () {
    const selectedOption = this.options[this.selectedIndex];
    const status = selectedOption.getAttribute("data-status");
    const arrow = this.parentElement.querySelector(".absolute.right-3 i"); // Fixed selector

    if (!arrow) return;

    // Remove all color classes
    arrow.classList.remove("text-green-500", "text-amber-500", "text-gray-400", "text-red-500");
    
    // Add appropriate color based on status
    if (status === "Available") {
      arrow.classList.add("text-green-500");
    } else if (status === "Occupied") {
      arrow.classList.add("text-amber-500");
    } else if (status === "Reserved") {
      arrow.classList.add("text-red-500");
    } else {
      arrow.classList.add("text-gray-400");
    }
  });

  // Initialize the arrow color on page load
  const initialSelected = select.options[select.selectedIndex];
  if (initialSelected) {
    const status = initialSelected.getAttribute("data-status");
    const arrow = select.parentElement.querySelector(".absolute.right-3 i");
    
    if (arrow) {
      if (status === "Available") {
        arrow.classList.add("text-green-500");
      } else if (status === "Occupied") {
        arrow.classList.add("text-amber-500");
      } else if (status === "Reserved") {
        arrow.classList.add("text-red-500");
      } else {
        arrow.classList.add("text-gray-400");
      }
    }
  }
}