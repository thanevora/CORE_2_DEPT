// Filter by status (your existing code)
document.getElementById('table-filter').addEventListener('click', function (e) {
  if (e.target.tagName.toLowerCase() !== 'a') return;

  const filter = e.target.getAttribute('data-filter');
  const cards = document.querySelectorAll('#tables-grid > div');

  cards.forEach(card => {
    const status = card.getAttribute('data-status');

    if (filter === 'all' || status === filter) {
      card.classList.remove('hidden');
    } else {
      card.classList.add('hidden');
    }
  });
});

// Search by table name
document.getElementById('table-search').addEventListener('input', function () {
  const searchValue = this.value.toLowerCase();
  const cards = document.querySelectorAll('#tables-grid > div');

  cards.forEach(card => {
    const tableName = card.querySelector('.table-name')?.textContent.toLowerCase() || '';
    
    if (tableName.includes(searchValue)) {
      card.classList.remove('hidden');
    } else {
      card.classList.add('hidden');
    }
  });
});
