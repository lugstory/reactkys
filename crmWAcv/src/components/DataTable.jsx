import PropTypes from 'prop-types';
import React from 'react';

const DataTable = ({
  data,
  columns,
  className,
  caption,
  emptyCaption,
  sortConfig,
  onSort,
  renderHeader,
  renderCell,
  renderActions,
  rowKey,
  rowId,
  rowClassName,
  selectable,
  selectedIds,
  onToggleSelect,
  wrapCells,
  onToggleWrap,
  onToggleSelectAll,
  actionsHeader,
}) => {
  const getSortIcon = (column) => {
    if (sortConfig?.key !== column) {
      return '';
    }
    return sortConfig.direction === 'asc' ? '▲' : '▼';
  };

  const getColumnClassName = (column) => (
    `col-${column} ${getSortIcon(column) ? 'sorted-colm' : ''}`
  );

  const isRowSelected = (id) => {
    if (!selectedIds) return false;
    return (
      selectedIds.has(id)
      || selectedIds.has(Number(id))
      || selectedIds.has(String(id))
    );
  };

  return (
    <table className={`${className} responsive-table ${wrapCells ? 'wrap-cells' : 'nowrap-cells'}`}>
      {caption || (data.length === 0 && emptyCaption) ? (
        <caption>{caption || emptyCaption}</caption>
      ) : null}
      <thead>
        <tr>
          {selectable ? (
            <th>
              {onToggleWrap ? (
                <button
                  type="button"
                  onClick={onToggleWrap}
                  className="table-wrap-toggle"
                  title="Přepnout zalamování textu"
                >
                  🔁
                </button>
              ) : null}
              {' '}
              {onToggleSelectAll ? (
                <input
                  type="checkbox"
                  checked={data.length > 0 && data.every((row) => isRowSelected(rowId ? rowId(row) : row.id))}
                  onChange={(event) => onToggleSelectAll(event.target.checked)}
                  title="Vybrat vše"
                  style={{ cursor: 'pointer', verticalAlign: 'middle', marginRight: '4px' }}
                />
              ) : null}
              Vybrat
            </th>
          ) : null}
          {columns.map((column) => (
            <th
              key={column}
              onClick={() => onSort?.(column)}
              className={getColumnClassName(column)}
              style={onSort ? { cursor: 'pointer' } : undefined}
            >
              {renderHeader ? renderHeader(column, data.length) : column}
              {' '}
              {getSortIcon(column)}
            </th>
          ))}
          {renderActions ? <th>{actionsHeader}</th> : null}
        </tr>
      </thead>
      <tbody>
        {data.map((row, rowIndex) => {
          const id = rowId ? rowId(row) : row.id;
          return (
            <tr
              key={rowKey ? rowKey(row) : (row.id ?? rowIndex)}
              id={rowClassName ? rowClassName(row) : undefined}
            >
              {selectable ? (
                <td>
                  <input
                    type="checkbox"
                    checked={isRowSelected(id)}
                    onClick={(event) => {
                      onToggleSelect?.(rowIndex, id, Boolean(event.shiftKey));
                    }}
                    onChange={() => {
                      // Handled by onClick to reliably capture shiftKey from MouseEvent
                    }}
                  />
                </td>
              ) : null}
              {columns.map((column) => (
                <td key={column} className={getColumnClassName(column)}>
                  {renderCell ? renderCell(row, column, rowIndex, wrapCells) : row[column]}
                </td>
              ))}
              {renderActions ? <td>{renderActions(row, rowIndex)}</td> : null}
            </tr>
          );
        })}
      </tbody>
    </table>
  );
};

DataTable.propTypes = {
  data: PropTypes.arrayOf(PropTypes.object).isRequired,
  columns: PropTypes.arrayOf(PropTypes.string).isRequired,
  className: PropTypes.string,
  caption: PropTypes.node,
  emptyCaption: PropTypes.node,
  sortConfig: PropTypes.shape({
    key: PropTypes.string,
    direction: PropTypes.string,
  }),
  onSort: PropTypes.func,
  renderHeader: PropTypes.func,
  renderCell: PropTypes.func,
  renderActions: PropTypes.func,
  rowKey: PropTypes.func,
  rowId: PropTypes.func,
  rowClassName: PropTypes.func,
  selectable: PropTypes.bool,
  selectedIds: PropTypes.instanceOf(Set),
  onToggleSelect: PropTypes.func,
  wrapCells: PropTypes.bool,
  onToggleWrap: PropTypes.func,
  onToggleSelectAll: PropTypes.func,
  actionsHeader: PropTypes.node,
};

DataTable.defaultProps = {
  className: '',
  caption: null,
  emptyCaption: null,
  sortConfig: { key: null, direction: 'asc' },
  onSort: undefined,
  renderHeader: undefined,
  renderCell: undefined,
  renderActions: undefined,
  rowKey: (row) => row.id,
  rowId: (row) => row.id,
  rowClassName: undefined,
  selectable: false,
  selectedIds: new Set(),
  onToggleSelect: undefined,
  wrapCells: false,
  onToggleWrap: undefined,
  onToggleSelectAll: undefined,
  actionsHeader: null,
};

export default DataTable;
