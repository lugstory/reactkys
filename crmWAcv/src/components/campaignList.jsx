/* eslint-disable jsx-a11y/control-has-associated-label */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/no-static-element-interactions */
import axios from 'axios';
import React, { useState, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import DataTable from './DataTable';
import { useUrl } from './UrlProvider';
import isSmall from '../utils/mobileDetect';

const getFirstPart = (text) => {
  const parts = text?.split(/\/\(kont\)/) || [];
  return parts[0];
};

const CampaignList = () => {
  const [campaigns, setCampaigns] = useState([]);
  const { apiUrl, user } = useUrl();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const navigate = useNavigate();
  const location = useLocation();
  const [isWrapped, setIsWrapped] = useState(false);

  const toggleWrap = () => {
    setIsWrapped(!isWrapped);
  };

  const fetchCampaigns = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${apiUrl}/campaigns/`);
      if (Array.isArray(response.data) && response.data.length === 0
        && response.data.msg !== undefined) {
        setError('Žádné kontakty.');
      } else {
        console.log(response.data);
        setCampaigns(response.data);
        setError(null);
      }
    } catch (err) {
      console.log(err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCampaigns();
  }, [apiUrl, location.key, location.state?.refresh]);

  const handleEditClick = (campaign) => {
    navigate(`/campaignAdd/${campaign.id}`);
  };
  const deleteCampaign = async (firmId) => {
    try {
      const response = await axios.delete(`${apiUrl}campaign/${firmId}`);
      if (response.status === 200) {
        // fetchData();
        setCampaigns((prevFirm) => prevFirm.filter((firm) => firm.id !== firmId));
      } else {
        setError('Smazání kontaktu selhalo');
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };
  const handleDelClick = (id) => {
    const confirmed = window.confirm('Chceš to fakt vymazat?');
    if (confirmed) {
      deleteCampaign(id);
    }
  };
  const handleClick = (id) => {
    navigate(`/getCampaignContacts/${id}`);
  };

  if (loading) {
    return <p className="no-data">Načítám...</p>;
  }
  if (error) {
    return (
      <p className="no-data">
        Error:
        {error}
      </p>
    );
  }

  const campaignColumns = [
    'id',
    'name',
    'created_date',
    'sent_date_time',
    'end_date',
    'recipient_count',
    'undelivered_count',
    'confirmed_received_count',
    'replied_count',
    'note',
  ];

  const columnHeaders = {
    id: 'ID',
    name: 'Název',
    created_date: 'Datum Vytvoření',
    sent_date_time: 'Datum odeslání',
    end_date: 'Datum ukončení',
    recipient_count: 'Počet adresátů (firem)',
    undelivered_count: 'Počet nedoručení',
    confirmed_received_count: 'Počet potvrzení o doručení',
    replied_count: 'Odpovědělo',
    note: 'Poznámka',
  };

  return (
    <div>
      <h1>Zasílání</h1>
      <DataTable
        data={campaigns}
        columns={campaignColumns}
        wrapCells={isWrapped}
        onToggleWrap={toggleWrap}
        renderHeader={(col) => columnHeaders[col] || col}
        renderCell={(campaign, col) => {
          if (col === 'name') {
            return (
              <span
                style={{ cursor: 'pointer', color: '#0366d6' }}
                onClick={() => handleClick(campaign.id)}
              >
                {getFirstPart(campaign.name)}
              </span>
            );
          }
          return campaign[col];
        }}
        renderActions={(campaign) => (
          user.user !== 'reader' ? (
            <div>
              <div className={isSmall() ? 'small-resolution' : ''}>
                <button type="button" onClick={() => handleEditClick(campaign)}>upravit</button>
                <button type="button" onClick={() => handleDelClick(campaign.id)} className="del-btn">smazat</button>
                <a href={`${apiUrl}campaignExport/${campaign.id}/?csvexport`} id="csv_export">CSV export</a>
              </div>
            </div>
          ) : null
        )}
      />
    </div>
  );
};

export default CampaignList;
