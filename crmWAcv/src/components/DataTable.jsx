import PropTypes from 'prop-types';
import React from 'react';

const DataTable = ({
  data,
  columns,
  className = '',
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
                  ↔
                </button>
              ) : null}
              {' '}
              Vybrat
            </th>
          ) : null}
          {columns.map((column) => (
            <th
              key={column}
              onClick={() => onSort?.(column)}
              className={getColumnClassName(column)}
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
          const id = rowId(row);
          return (
            <tr
              key={rowKey(row)}
              id={rowClassName ? rowClassName(row) : undefined}
            >
              {selectable ? (
                <td>
                  <input
                    type="checkbox"
                    checked={selectedIds.has(id)}
                    onChange={(event) => onToggleSelect(rowIndex, id, event.nativeEvent.shiftKey)}
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
  actionsHeader: null,
};

export default DataTable;
