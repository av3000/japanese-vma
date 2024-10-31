import React, { useState, useEffect } from "react";
import { Link, useHistory, useParams } from "react-router-dom";
import { Button, Modal } from "react-bootstrap";

import Spinner from "../../assets/images/spinner.gif";
import {
  BASE_URL,
  HTTP_METHOD,
  ObjectTemplates,
  LIST_ACTIONS,
} from "../../shared/constants";
import { apiCall } from "../../services/api";
import Hashtags from "../ui/hashtags";

const WordDetails = ({ currentUser }) => {
  const [word, setWord] = useState({});
  const [kanjis, setKanjis] = useState([]);
  const [articles, setArticles] = useState([]);
  const [lists, setLists] = useState([]);
  const [showModal, setShowModal] = useState(false);
  const [wordIsKnown, setWordIsKnown] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [loadingListIds, setLoadingListIds] = useState([]);

  const { word_id } = useParams();
  const history = useHistory();

  useEffect(() => {
    getWordDetails();
    if (currentUser.isAuthenticated) {
      getUserWordLists();
    }
  }, [currentUser.isAuthenticated]);

  const getWordDetails = async () => {
    try {
      setIsLoading(true);
      const res = await apiCall(
        HTTP_METHOD.GET,
        `${BASE_URL}/api/word/${word_id}`
      );

      const processedWord = {
        ...res,
        meaning: res.meaning.split("|").join(", "),
      };

      setWord(processedWord);
      setKanjis(res.kanjis.data || []);
      setArticles(res.articles.data || []);
    } catch (error) {
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const getUserWordLists = async () => {
    try {
      setIsLoading(true);
      const res = await apiCall(
        HTTP_METHOD.POST,
        `${BASE_URL}/api/user/lists/contain`,
        {
          elementId: word_id,
        }
      );

      const knownLists = res.lists.filter(
        (list) =>
          list.type === ObjectTemplates.KNOWNWORDS && list.elementBelongsToList
      );
      setWordIsKnown(knownLists.length > 0);

      setLists(
        res.lists.filter(
          (list) =>
            list.type === ObjectTemplates.KNOWNWORDS ||
            list.type === ObjectTemplates.WORDS
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
      history.push("/login");
    } else {
      setShowModal((prevShow) => !prevShow);
    }
  };

  const addToOrRemoveFromList = async (listId, action) => {
    try {
      setLoadingListIds((prev) => [...prev, listId]);
      const endpoint =
        action === LIST_ACTIONS.ADD_ITEM
          ? "additemwhileaway"
          : "removeitemwhileaway";
      const url = `${BASE_URL}/api/user/list/${endpoint}`;

      await apiCall(HTTP_METHOD.POST, url, {
        listId,
        elementId: word_id,
      });

      setLists((prevLists) =>
        prevLists.map((list) =>
          list.id === listId
            ? {
                ...list,
                elementBelongsToList: action === LIST_ACTIONS.ADD_ITEM,
              }
            : list
        )
      );

      if (action === LIST_ACTIONS.ADD_ITEM) {
        if (
          lists.find(
            (list) =>
              list.id === listId && list.type === ObjectTemplates.KNOWNWORDS
          )
        ) {
          setWordIsKnown(true);
        }
      } else {
        const stillKnown = lists.some(
          (list) =>
            list.type === ObjectTemplates.KNOWNWORDS &&
            list.elementBelongsToList &&
            list.id !== listId
        );
        setWordIsKnown(stillKnown);
      }
    } catch (error) {
      console.error(error);
    } finally {
      setLoadingListIds((prev) => prev.filter((id) => id !== listId));
    }
  };

  const renderWordDetails = () => {
    return (
      <div className="row justify-content-center mt-5">
        <div className="col-md-4">
          <h1>{word.word}</h1>
          <p>Furigana: {word.furigana}</p>
        </div>
        <div className="col-md-4">
          <p>Type: {word.word_type}</p>
        </div>
        <div className="col-md-4">
          <p>
            JLPT: {word.jlpt} <br /> Meaning: {word.meaning}
          </p>
          {wordIsKnown && (
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
    );
  };

  const renderKanjiList = () => {
    return (
      <>
        <h4>Kanjis ({kanjis.length}) results</h4>
        <div className="container">
          {kanjis.map((kanji) => {
            const meanings = kanji.meaning.split("|").slice(0, 3).join(", ");
            return (
              <div className="row justify-content-center mt-5" key={kanji.id}>
                <div className="col-md-10">
                  <div className="row">
                    <div className="col-md-6">
                      <h3>{kanji.kanji}</h3>
                    </div>
                    <div className="col-md-4">{meanings}</div>
                    <div className="col-md-2">
                      <Link to={`/kanji/${kanji.id}`} className="float-right">
                        Open
                      </Link>
                    </div>
                  </div>
                  <hr />
                </div>
              </div>
            );
          })}
        </div>
      </>
    );
  };

  const renderArticleList = () => {
    return (
      <>
        <h4>Articles ({articles.length}) results</h4>
        <div className="container">
          {articles.map((article) => (
            <div className="row justify-content-center mt-5" key={article.id}>
              <div className="col-md-12">
                <div className="row">
                  <div className="col-md-8">
                    <h3>{article.title_jp}</h3>
                    <Hashtags hashtags={article.hashtags} />
                  </div>
                  <div className="col-md-2">
                    <p>
                      Views: {article.viewsTotal} <br />
                      Likes: {article.likesTotal} <br />
                      Comments: {article.commentsTotal}
                    </p>
                  </div>
                  <div className="col-md-2">
                    <Link
                      to={`/article/${article.id}`}
                      className="float-right"
                      target="_blank"
                    >
                      Open
                    </Link>
                  </div>
                </div>
                <hr />
              </div>
            </div>
          ))}
        </div>
      </>
    );
  };

  const renderAddModal = () => {
    return (
      <Modal show={showModal} onHide={toggleModal}>
        <Modal.Header closeButton>
          <Modal.Title>Choose Word List to add</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {lists.map((list) => {
            const isLoadingList = loadingListIds.includes(list.id);
            return (
              <div
                key={list.id}
                className="d-flex justify-content-between mb-2"
              >
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
                  disabled={isLoadingList}
                >
                  {isLoadingList ? (
                    <span className="spinner-border spinner-border-sm"></span>
                  ) : list.elementBelongsToList ? (
                    "Remove"
                  ) : (
                    "Add"
                  )}
                </Button>
              </div>
            );
          })}
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
    );
  };

  if (isLoading) {
    return (
      <div className="container mt-5">
        <div className="row justify-content-center">
          <img src={Spinner} alt="Loading..." />
        </div>
      </div>
    );
  }

  return (
    <div className="container">
      <span className="mt-4">
        <Link to="/words" className="tag-link">
          Back
        </Link>
      </span>
      {renderWordDetails()}
      <hr />
      {renderKanjiList()}
      <hr />
      {renderArticleList()}
      {renderAddModal()}
    </div>
  );
};

export default WordDetails;
