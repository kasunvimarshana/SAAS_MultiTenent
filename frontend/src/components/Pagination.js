import React from 'react';
import './Pagination.css';

const Pagination = ({ pagination, onPageChange }) => {
  if (!pagination || pagination.last_page <= 1) return null;

  const { current_page, last_page, total } = pagination;

  const pages = [];
  const start = Math.max(1, current_page - 2);
  const end = Math.min(last_page, current_page + 2);

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return (
    <div className="pagination">
      <span className="pagination-info">
        {total} total records, page {current_page} of {last_page}
      </span>
      <div className="pagination-controls">
        <button
          onClick={() => onPageChange(current_page - 1)}
          disabled={current_page === 1}
          className="page-btn"
        >
          ‹
        </button>
        {start > 1 && <button className="page-btn" onClick={() => onPageChange(1)}>1</button>}
        {start > 2 && <span className="ellipsis">...</span>}
        {pages.map(page => (
          <button
            key={page}
            onClick={() => onPageChange(page)}
            className={`page-btn ${page === current_page ? 'active' : ''}`}
          >
            {page}
          </button>
        ))}
        {end < last_page - 1 && <span className="ellipsis">...</span>}
        {end < last_page && <button className="page-btn" onClick={() => onPageChange(last_page)}>{last_page}</button>}
        <button
          onClick={() => onPageChange(current_page + 1)}
          disabled={current_page === last_page}
          className="page-btn"
        >
          ›
        </button>
      </div>
    </div>
  );
};

export default Pagination;
