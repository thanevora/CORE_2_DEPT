
 // This would be populated from your data source
  document.getElementById('reservationTableBody').innerHTML = `
    <tr class="hover:bg-blue-50 transition-colors">
      <td class="px-6 py-4 text-blue-900 font-medium">1</td>
      <td class="px-6 py-4 text-blue-900"></td>
      <td class="px-6 py-4 text-blue-900"></td>
      <td class="px-6 py-4 text-blue-900"></td>
      <td class="px-6 py-4 text-blue-900"></td>
      <td class="px-6 py-4 text-blue-900"></td>
      <td class="px-6 py-4 text-blue-900"></td>
      <td class="px-6 py-4">
        <button class="action-btn btn btn-circle btn-sm btn-ghost text-blue-600 hover:bg-blue-100">
          <i class="fas fa-ellipsis-v"></i>
        </button>
      </td>
    </tr>
  `;

  // Modal handling
  const actionBtns = document.querySelectorAll('.action-btn');
  const modal = document.getElementById('actionModal');
  const closeBtn = document.querySelector('.modal-close');
  
  actionBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      const rect = e.target.getBoundingClientRect();
      modal.style.top = `${rect.bottom + window.scrollY}px`;
      modal.style.left = `${rect.left + window.scrollX - 150}px`;
      modal.classList.remove('hidden');
    });
  });

  closeBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
  });

  window.addEventListener('click', (e) => {
    if (e.target === modal || e.target.classList.contains('modal-overlay')) {
      modal.classList.add('hidden');
    }
  });


  // calendar crude reservations list

  function getStatusBadge(status) {
  switch (status.toLowerCase()) {
    case "pending":
      return 'bg-yellow-200 text-yellow-800';
    case "approved":
      return 'bg-green-200 text-green-800';
    case "rejected":
      return 'bg-red-200 text-red-800';
    case "cancelled":
      return 'bg-slate-300 text-slate-800';
    default:
      return 'bg-gray-200 text-gray-800';
  }
}


// crude fumctionalities

