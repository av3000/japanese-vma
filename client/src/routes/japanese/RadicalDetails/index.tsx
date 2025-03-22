// @ts-nocheck

import React, { useState, useEffect } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { Button, Modal } from "react-bootstrap";
import Spinner from "@/assets/images/spinner.gif";
import {
  BASE_URL,
  HTTP_METHOD,
  ObjectTemplates,
  LIST_ACTIONS,
} from "@/shared/constants";
import { apiCall } from "@/services/api";

const RadicalDetails: React.FC = ({ currentUser }) => {
  const [radical, setRadical] = useState({});
  const [lists, setLists] = useState([]);
  const [showModal, setShowModal] = useState(false);
  const [radicalIsKnown, setRadicalIsKnown] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [loadingListIds, setLoadingListIds] = useState([]);

  const { radical_id } = useParams();
  const navigate = useNavigate();

  useEffect(() => {
    getRadicalDetails();
    if (currentUser.isAuthenticated) {
      getUserRadicalLists();
    }
  }, [currentUser.isAuthenticated]);

  const getRadicalDetails = async () => {
    try {
      setIsLoading(true);
      const res = await apiCall(
        HTTP_METHOD.GET,
        `${BASE_URL}/api/radical/${radical_id}`
      );
      setRadical(res);
    } catch (error) {
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const getUserRadicalLists = async () => {
    try {
      setIsLoading(true);
      const res = await apiCall(
        HTTP_METHOD.POST,
        `${BASE_URL}/api/user/lists/contain`,
        {
          elementId: radical_id,
        }
      );
      const knownLists = res.lists.filter(
        (list) =>
          list.type === ObjectTemplates.KNOWNRADICALS &&
          list.elementBelongsToList
      );
      setRadicalIsKnown(knownLists.length > 0);
      setLists(
        res.lists.filter(
          (list) =>
            list.type === ObjectTemplates.KNOWNRADICALS ||
            list.type === ObjectTemplates.RADICALS
        )
      );
    } catch (error) {
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const toggleModal = () => {
    if (!currentUser.isAuthenticated) {
      navigate("/login");
    } else {
      setShowModal((prevShow) => !prevShow);
    }
  };

  const addToOrRemoveFromList = async (id, action) => {
    try {
      setLoadingListIds((prev) => [...prev, id]);
      const endpoint =
        action === LIST_ACTIONS.ADD_ITEM
          ? "additemwhileaway"
          : "removeitemwhileaway";
      const url = `${BASE_URL}/api/user/list/${endpoint}`;

      await apiCall(HTTP_METHOD.POST, url, {
        listId: id,
        elementId: radical_id,
      });

      setLists((prevLists) =>
        prevLists.map((list) =>
          list.id === id
            ? {
                ...list,
                elementBelongsToList: action === LIST_ACTIONS.ADD_ITEM,
              }
            : list
        )
      );
      setRadicalIsKnown(
        action === LIST_ACTIONS.ADD_ITEM
          ? true
          : lists.some(
              (list) =>
                list.type === ObjectTemplates.KNOWNRADICALS &&
                list.elementBelongsToList
            )
      );
    } catch (error) {
      console.error(error);
    } finally {
      setLoadingListIds((prev) => prev.filter((loadingId) => loadingId !== id));
    }
  };

  return (
    <div className="container">
      <div className="mt-5">
        <Link to="/radicals" className="tag-link">
          Back
        </Link>
      </div>
      {isLoading ? (
        <div className="container">
          <div className="row justify-content-center">
            <img src={Spinner} alt="spinner" />
          </div>
        </div>
      ) : (
        <div className="row justify-content-center mt-5">
          <div className="col-md-6">
            <h1>
              {radical.radical} <br />
              {radical.hiragana}
            </h1>
          </div>
          <div className="col-md-6">
            <p>meaning: {radical.meaning}</p>
            <p>strokes: {radical.strokes}</p>
            {radicalIsKnown && (
              <i className="fas fa-check-circle text-success"> Learned</i>
            )}
            <button
              onClick={toggleModal}
              className="btn btn-outline brand-button float-right"
              variant="outline-primary"
            >
              <i className="far fa-bookmark fa-lg"></i>
            </button>
          </div>
        </div>
      )}

      <hr />
      {radical.kanjis && radical.kanjis.length > 0 && (
        <>
          <h4>kanjis ({radical.kanjis.length}) results</h4>
          {radical.kanjis.map((kanji) => (
            <div className="row justify-content-center mt-5" key={kanji.id}>
              <div className="col-md-8">
                <div className="row justify-content-center">
                  <div className="col-md-6">
                    <h3>{kanji.kanji}</h3>
                  </div>
                  <div className="col-md-4">{kanji.meaning}</div>
                  <div className="col-md-2">
                    <Link to={`/kanji/${kanji.id}`} className="float-right">
                      <i className="fas fa-external-link-alt fa-lg"></i>
                    </Link>
                  </div>
                </div>
                <hr />
              </div>
            </div>
          ))}
        </>
      )}

      <Modal show={showModal} onHide={toggleModal}>
        <Modal.Header closeButton>
          <Modal.Title>Choose Radical List to add</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {lists.map((list) => (
            <div key={list.id} className="d-flex justify-content-between">
              <Link to={`/list/${list.id}`}>{list.title}</Link>
              <Button
                variant={list.elementBelongsToList ? "danger" : "primary"}
                size="sm"
                onClick={() =>
                  addToOrRemoveFromList(
                    list.id,
                    list.elementBelongsToList
                      ? LIST_ACTIONS.REMOVE_ITEM
                      : LIST_ACTIONS.ADD_ITEM
                  )
                }
                disabled={loadingListIds.includes(list.id)}
              >
                {loadingListIds.includes(list.id) ? (
                  <span className="spinner-border spinner-border-sm"></span>
                ) : list.elementBelongsToList ? (
                  "Remove"
                ) : (
                  "Add"
                )}
              </Button>
            </div>
          ))}
          <small>
            <Link to="/newlist">Create a new list?</Link>
          </small>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={toggleModal}>
            Close
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
};

export default RadicalDetails;
