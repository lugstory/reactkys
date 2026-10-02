/* eslint-disable jsx-a11y/control-has-associated-label */
import axios from 'axios';
import PropTypes from 'prop-types';
import React, { useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import EditMeetForm from './editMeetForm';
import { useUrl } from './UrlProvider';
import { convertDateTimeToCzech } from '../utils/czechdates';

const MeetList = ({
  firmId: propFirmId, firmName: propFirmName, onClose,
}) => {
  const { firmId: routeFirmId } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const firmId = propFirmId ?? routeFirmId;
  const { apiUrl } = useUrl();
  const [currentFirmName, setCurrentFirmName] = useState(
    propFirmName || location.state?.firmName || '',
  );
  const [meets, setMeets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedMeet, setSelectedMeet] = useState(null);

  useEffect(() => {
    if (!currentFirmName && firmId) {
      axios.get(`${apiUrl}firm/${firmId}`)
        .then((response) => {
          if (Array.isArray(response.data) && response.data.length > 0 && response.data[0].name) {
            setCurrentFirmName(response.data[0].name);
          } else if (response.data && response.data.name) {
            setCurrentFirmName(response.data.name);
          }
        })
        .catch(() => {});
    }
  }, [apiUrl, firmId, currentFirmName]);

  const displayFirmName = currentFirmName ? currentFirmName.split('/(kont)')[0] : 'Firma';

  useEffect(() => {
    const fetchMeets = async () => {
      try {
        const response = await axios.get(`${apiUrl}meets/${firmId}`);
        if (Array.isArray(response.data) && response.data.length === 0
        && response.data.msg !== undefined) {
          setError('Žádné schůzky.');
        } else {
          console.log(response.data);
          setMeets(response.data);
        }
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchMeets();
  }, [firmId]);

  const deleteMeet = async (meetId) => {
    try {
      const response = await axios.delete(`${apiUrl}meets/${meetId}`);
      if (response.status === 200) {
        setMeets((prevMeets) => prevMeets.filter((meet) => meet.id !== meetId));
      } else {
        setError('Smazání kontaktu selhalo');
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handledelClick = (meet) => {
    const confirmed = window.confirm('Chceš to fakt vymazat?');
    if (confirmed) {
      deleteMeet(meet.id);
    }
  };
  const handleEditClick = (meet) => {
    console.log(meet);
    setSelectedMeet(meet);
  };
  const handleClose = () => {
    setSelectedMeet(null);
    if (onClose) {
      onClose();
      return;
    }
    navigate('/firm');
  };
  const handleSave = (meetupdatedMeet) => {
    const existingMeet = meets.find((meet) => meet.id === meetupdatedMeet.id);
    const updatedMeet = {
      ...meetupdatedMeet,
      date_time: convertDateTimeToCzech(meetupdatedMeet.date_time),
    };
    console.log(updatedMeet);
    if (!existingMeet) {
      setMeets([...meets, meetupdatedMeet]);
    } else {
      setMeets(meets.map(
        (meet) => (meet.id === meetupdatedMeet.id ? meetupdatedMeet : meet),
      ));
    }
    setSelectedMeet(null); // Close the form after saving
  };
  const handleKeyDown = (event) => {
    if (event.key === 'Escape') {
      if (!selectedMeet) {
        if (onClose) {
          onClose(null);
        } else {
          navigate('/firm');
        }
      }
    }
  };
  useEffect(() => {
    window.addEventListener('keydown', handleKeyDown);
    return () => {
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [selectedMeet]);

  if (!firmId) {
    return <p className="no-data">Chybí ID firmy.</p>;
  }

  if (loading) {
    return <p className="no-data">Načítám...</p>;
  }
  if (error) {
    return (
      <p className="no-data">
        Chyba:
        {error}
      </p>
    );
  }
  return (
    <div className="responsive-table page-view">
      <div className="subview-header">
        <button type="button" onClick={handleClose} className="back-btn">
          &larr; Zpět na seznam firem
        </button>
      </div>
      {selectedMeet ? (
        <div className="floating-layer">
          <EditMeetForm meet={selectedMeet} onSave={handleSave} onClose={() => setSelectedMeet(null)} firmName={displayFirmName} />
        </div>
      ) : (
        <table className="responsive-table">
          <caption><h3>{`${displayFirmName} - schůzky`}</h3></caption>
          <thead>
            <tr>
              <th>Datum a čas</th>
              <th>Poznámka</th>
              <th />
              <th />
            </tr>
          </thead>
          <tbody>
            {meets.map((meet) => (
              <tr key={meet.id}>
                <td data-label="Datum a čas">{convertDateTimeToCzech(meet.date_time)}</td>
                <td data-label="Poznámka">{meet.notes}</td>
                <td><button type="button" onClick={() => handleEditClick(meet)}>upravit</button></td>
                <td><button type="button" onClick={() => handledelClick(meet)} className="del-btn">smazat</button></td>
              </tr>
            ))}
            <tr>
              <td />
              <td />
              <td />
              <td><button type="button" onClick={() => handleEditClick({ firm_id: firmId })}>Přidat schůzku</button></td>
            </tr>
          </tbody>
        </table>
      )}
    </div>
  );
};

export default MeetList;

MeetList.propTypes = {
  firmId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  firmName: PropTypes.string,
  onClose: PropTypes.func,
};
