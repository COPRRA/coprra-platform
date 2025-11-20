/**
 * Live Search functionality for product autocomplete
 */

const LIVE_SEARCH_ENDPOINT = '/api/products/autocomplete';
const MIN_QUERY_LENGTH = 2;
const DEBOUNCE_DELAY = 300;

let searchTimeout = null;
let currentResults = [];

const createDropdown = () => {
  const dropdown = document.createElement('div');
  dropdown.id = 'live-search-dropdown';
  dropdown.className = 'absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto';
  dropdown.style.display = 'none';
  return dropdown;
};

const createDropdownItem = (product) => {
  const item = document.createElement('a');
  item.href = product.url;
  item.className = 'block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition';
  item.textContent = product.name;
  return item;
};

const showDropdown = (container, results) => {
  const dropdown = container.querySelector('#live-search-dropdown') || createDropdown();
  
  // Clear existing items
  dropdown.innerHTML = '';
  
  if (results.length === 0) {
    const noResults = document.createElement('div');
    noResults.className = 'px-4 py-2 text-sm text-gray-500 dark:text-gray-400';
    noResults.textContent = 'No products found';
    dropdown.appendChild(noResults);
  } else {
    results.forEach(product => {
      dropdown.appendChild(createDropdownItem(product));
    });
  }
  
  if (!container.querySelector('#live-search-dropdown')) {
    container.appendChild(dropdown);
  }
  
  dropdown.style.display = 'block';
};

const hideDropdown = (container) => {
  const dropdown = container.querySelector('#live-search-dropdown');
  if (dropdown) {
    dropdown.style.display = 'none';
  }
};

const performSearch = async (query, container) => {
  if (query.length < MIN_QUERY_LENGTH) {
    hideDropdown(container);
    return;
  }

  try {
    const response = await fetch(`${LIVE_SEARCH_ENDPOINT}?q=${encodeURIComponent(query)}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Search failed');
    }

    const data = await response.json();
    currentResults = data.data || [];
    showDropdown(container, currentResults);
  } catch (error) {
    console.error('Live search error:', error);
    hideDropdown(container);
  }
};

const debounceSearch = (query, container) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  searchTimeout = setTimeout(() => {
    performSearch(query, container);
  }, DEBOUNCE_DELAY);
};

const initializeLiveSearch = () => {
  // Find all search inputs
  const searchInputs = document.querySelectorAll('input[name="search"], input[placeholder*="Search"], input[placeholder*="بحث"]');
  
  searchInputs.forEach(input => {
    const container = input.closest('form') || input.parentElement;
    
    if (!container) {
      return;
    }

    // Make container relative for dropdown positioning
    if (getComputedStyle(container).position === 'static') {
      container.style.position = 'relative';
    }

    input.addEventListener('input', (e) => {
      const query = e.target.value.trim();
      debounceSearch(query, container);
    });

    input.addEventListener('blur', () => {
      // Delay hiding to allow clicking on dropdown items
      setTimeout(() => {
        hideDropdown(container);
      }, 200);
    });

    input.addEventListener('focus', (e) => {
      const query = e.target.value.trim();
      if (query.length >= MIN_QUERY_LENGTH && currentResults.length > 0) {
        showDropdown(container, currentResults);
      }
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!container.contains(e.target)) {
        hideDropdown(container);
      }
    });
  });
};

document.addEventListener('DOMContentLoaded', initializeLiveSearch);

