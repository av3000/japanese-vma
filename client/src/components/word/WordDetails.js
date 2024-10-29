import React, { Component } from "react";
import axios from "axios";
import { Button, Modal } from "react-bootstrap";
import { Link } from "react-router-dom";

import Spinner from "../../assets/images/spinner.gif";
import { BASE_URL } from "../../shared/constants";

class KanjiDetails extends Component {
  constructor(props) {
    super(props);
    this.state = {
      pagination: [],
      word: {},
      kanjis: {},
      sentences: {},
      articles: {},
      paginateObject: {},
      searchHeading: "",
      searchTotal: "",
      filters: [],
      lists: [],
      show: false,
      wordIsKnown: false,
    };

    this.addToList = this.addToList.bind(this);
    this.removeFromList = this.removeFromList.bind(this);
    this.openModal = this.openModal.bind(this);
    this.handleClose = this.handleClose.bind(this);
    this.getUserWordLists = this.getUserWordLists.bind(this);
  }

  wordId = this.props.match.params.word_id;

  componentDidMount() {
    this.getWordDetails();
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.currentUser.isAuthenticated) {
      this.getUserWordLists();
    }
  }

  handleClose() {
    this.setState({ show: !this.state.show });
  }

  getWordDetails() {
    const url = BASE_URL + "/api/word/" + this.wordId;

    axios
      .get(url)
      .then((res) => {
        res.data.meaning = res.data.meaning.split("|");
        res.data.meaning = res.data.meaning.join(", ");

        this.setState({
          word: res.data,
          paginateObject: res,
          kanjis: res.data.kanjis,
          articles: res.data.articles,
          sentences: res.data.sentences,
        });
      })
      .catch((err) => {
        console.log(err);
      });

    if (this.props.currentUser.isAuthenticated) {
      this.getUserWordLists();
    }
  }

  getUserWordLists() {
    const url = BASE_URL + "/api/user/lists/contain";

    return axios
      .post(url, {
        elementId: this.wordId,
      })
      .then((res) => {
        const newState = Object.assign({}, this.state);
        newState.lists = res.data.lists.filter((list) => {
          if (list.type === 3 && list.elementBelongsToList) {
            newState.wordIsKnown = true;
          }
          if (list.type === 3 || list.type === 7) {
            return list;
          }
        });

        this.setState(newState);
      })
      .catch((err) => {
        console.log(err);
      });
  }

  openModal() {
    if (this.props.currentUser.isAuthenticated === false) {
      this.props.history.push("/login");
    } else {
      this.setState({ show: !this.state.show });
    }
  }

  addToList(id) {
    const url = BASE_URL + "/api/user/list/additemwhileaway";

    axios
      .post(url, {
        listId: id,
        elementId: this.props.match.params.word_id,
      })
      .then((res) => {
        const newState = Object.assign({}, this.state);
        newState.lists.find((list) => {
          if (list.id === id) {
            if (list.type === 3) {
              newState.wordIsKnown = true;
            }
            return (list.elementBelongsToList = true);
          }
        });

        this.setState(newState);
      })
      .catch((err) => console.log(err));
  }

  removeFromList(id) {
    const url = BASE_URL + "/api/user/list/removeitemwhileaway";

    axios
      .post(url, {
        listId: id,
        elementId: this.wordId,
      })
      .then((res) => {
        const newState = Object.assign({}, this.state);
        newState.lists.find((list) => {
          if (list.id === id) {
            if (list.type === 3) {
              newState.wordIsKnown = false;
            }
            return (list.elementBelongsToList = false);
          }
        });

        this.setState(newState);
      })
      .catch((err) => console.log(err));
  }

  render() {
    const { word, kanjis, articles } = this.state;
    const singleWord = word ? (
      <div className="row justify-content-center mt-5">
        <div className="col-md-4">
          <h1>
            {word.word} <br />
          </h1>
          <p>furigana: {word.furigana},</p>
        </div>
        <div className="col-md-4">
          <p>type: {word.word_type},</p>
        </div>
        <div className="col-md-4">
          <p>
            jlpt: {word.jlpt}, <br /> meaning: {word.meaning}
          </p>
          <p className="float-right">
            {this.state.wordIsKnown ? (
              <i className="fas fa-check-circle text-success"> Learned</i>
            ) : (
              ""
            )}
            <i
              onClick={this.openModal}
              className="far fa-bookmark ml-3 fa-lg mr-2"
            ></i>
          </p>
        </div>
      </div>
    ) : (
      <div className="container mt-5">
        <div className="row justify-content-center">
          <img src={Spinner} alt="spinner loading" />
        </div>
      </div>
    );

    const kanjiList = kanjis.data
      ? kanjis.data.map((kanji) => {
          kanji.meaning = kanji.meaning.split("|");
          kanji.meaning = kanji.meaning.slice(0, 3);
          kanji.meaning = kanji.meaning.join(", ");

          return (
            <div className="row justify-content-center mt-5" key={kanji.id}>
              <div className="col-md-10">
                <div className="container">
                  <div className="row justify-content-center">
                    <div className="col-md-6">
                      <h3>{kanji.kanji}</h3>
                    </div>
                    <div className="col-md-4">{kanji.meaning}</div>
                    <div className="col-md-2">
                      <Link
                        to={`/api/kanji/${kanji.id}`}
                        className="float-right"
                      >
                        <i className="fas fa-external-link-alt fa-lg"></i>
                      </Link>
                    </div>
                  </div>
                </div>
                <hr />
              </div>
              <hr />
            </div>
          );
        })
      : "";

    const articleList = articles.data
      ? articles.data.map((article) => {
          article.hashtags = article.hashtags.slice(0, 3);
          return (
            <div className="row justify-content-center mt-5" key={article.id}>
              <div className="col-md-12">
                <div className="container">
                  <div className="row justify-content-center">
                    <div className="col-md-8">
                      <h3>{article.title_jp}</h3>
                      <p>
                        {article.hashtags.map((tag) => (
                          <span key={tag.id} className="tag-link" to="/">
                            {tag.content}{" "}
                          </span>
                        ))}
                      </p>
                    </div>
                    <div className="col-md-2">
                      <p>
                        Views:{" "}
                        {article.viewsTotal +
                          Math.floor(Math.random() * Math.floor(20))}{" "}
                        <br />
                        Likes:{" "}
                        {article.likesTotal +
                          Math.floor(Math.random() * Math.floor(20))}{" "}
                        <br />
                        Comments:{" "}
                        {article.commentsTotal +
                          Math.floor(Math.random() * Math.floor(20))}{" "}
                        <br />
                      </p>
                    </div>
                    <div className="col-md-2">
                      <Link
                        to={`/article/${article.id}`}
                        className="float-right"
                        target="_blank"
                      >
                        details...{" "}
                      </Link>
                    </div>
                  </div>
                </div>
                <hr />
              </div>
              <hr />
            </div>
          );
        })
      : "";

    const addModal = this.state.lists
      ? this.state.lists.map((list) => {
          return (
            <div key={list.id}>
              <div className="col-9">
                {" "}
                <Link to={`/list/${list.id}`}>{list.title}</Link>
                {list.elementBelongsToList ? (
                  <button
                    className="btn btn-sm btn-danger"
                    onClick={this.removeFromList.bind(this, list.id)}
                  >
                    -
                  </button>
                ) : (
                  <button
                    className="btn btn-sm btn-light"
                    onClick={this.addToList.bind(this, list.id)}
                  >
                    +
                  </button>
                )}
              </div>
            </div>
          );
        })
      : "";

    return (
      <div className="container">
        <span className="mt-4">
          <Link to="/words" className="tag-link">
            Back
          </Link>
        </span>
        {singleWord}
        <hr />
        {kanjis.data ? <h4>kanjis ({kanjis.data.length}) results</h4> : ""}
        <div className="container">{kanjiList}</div>
        <hr />
        <div className="container">sentences (0) results</div>
        <hr />
        {articles.data ? (
          <h4>articles ({articles.data.length}) results</h4>
        ) : (
          ""
        )}
        <div className="container">{articleList}</div>

        <Modal show={this.state.show} onHide={this.handleClose}>
          <Modal.Header closeButton>
            <Modal.Title>Choose Word List to add</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {addModal}
            <small>
              {" "}
              <Link to="/newlist">Create a new list?</Link>{" "}
            </small>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={this.handleClose}>
              Close
            </Button>
          </Modal.Footer>
        </Modal>
      </div>
    );
  }
}

export default KanjiDetails;
