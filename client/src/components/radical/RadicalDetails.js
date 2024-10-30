import React, { Component } from "react";
import axios from "axios";
import { Link } from "react-router-dom";
import { Button, Modal } from "react-bootstrap";

import Spinner from "../../assets/images/spinner.gif";
import { BASE_URL, HTTP_METHOD, ObjectTemplates } from "../../shared/constants";
import { apiCall } from "../../services/api";

class RadicalDetails extends Component {
  constructor(props) {
    super(props);
    this.state = {
      radical: {},
      lists: [],
      show: false,
      radicalIsKnown: false,
    };

    this.addToList = this.addToList.bind(this);
    this.removeFromList = this.removeFromList.bind(this);
    this.openModal = this.openModal.bind(this);
    this.handleClose = this.handleClose.bind(this);
    this.getUserRadicalLists = this.getUserRadicalLists.bind(this);
  }

  radicalId = this.props.match.params.radical_id;

  handleClose() {
    this.setState({ show: !this.state.show });
  }

  componentDidMount() {
    this.getRadicalDetails();

    if (this.props.currentUser.isAuthenticated) {
      this.getUserRadicalLists();
    }
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.currentUser.isAuthenticated) {
      this.getUserRadicalLists();
    }
  }

  getRadicalDetails() {
    const url = BASE_URL + "/api/radical/" + this.radicalId;

    axios
      .get(url)
      .then((res) => {
        this.setState({
          radical: res.data,
        });
      })
      .catch((err) => {
        console.log(err);
      });
  }

  async getUserRadicalLists() {
    try {
      const url = `${BASE_URL}/api/user/lists/contain`;

      const res = await apiCall(HTTP_METHOD.POST, url, {
        elementId: this.radicalId,
      });

      this.setState((prevState) => ({
        ...prevState,
        radicalIsKnown: res.data.lists.some(
          (list) =>
            list.type === ObjectTemplates.KNOWNRADICALS &&
            list.elementBelongsToList
        ),
        lists: res.data.lists.filter(
          (list) =>
            list.type === ObjectTemplates.KNOWNRADICALS ||
            list.type === ObjectTemplates.RADICALS
        ),
      }));
    } catch (error) {
      console.log(error);
    }
  }

  openModal() {
    this.props.currentUser.isAuthenticated
      ? this.setState({ show: !this.state.show })
      : this.props.history.push("/login");
  }

  addToList(id) {
    const url = BASE_URL + "/api/user/list/additemwhileaway";
    axios
      .post(url, {
        listId: id,
        elementId: this.radicalId,
      })
      .then((res) => {
        const newState = Object.assign({}, this.state);
        newState.lists.find((list) => {
          if (list.id === id) {
            if (list.type === 1) {
              newState.radicalIsKnown = true;
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
        elementId: this.radicalId,
      })
      .then((res) => {
        const newState = Object.assign({}, this.state);
        newState.lists.find((list) => {
          if (list.id === id) {
            if (list.type === 1) {
              newState.radicalIsKnown = false;
            }
            return (list.elementBelongsToList = false);
          }
        });

        this.setState(newState);
      })
      .catch((err) => console.log(err));
  }

  render() {
    const { radical } = this.state;
    const singleRadical = radical ? (
      <div className="row justify-content-center mt-5">
        <div className="col-md-6">
          <h1>
            {radical.radical} <br />
            {radical.hiragana}
          </h1>
        </div>
        <div className="col-md-6">
          <p>meaning: {radical.meaning},</p>
          <p>strokes: {radical.strokes}</p>
          <p className="float-right">
            {this.state.radicalIsKnown ? (
              <i className="fas fa-check-circle text-success"> Learned</i>
            ) : (
              ""
            )}
            <i
              onClick={this.openModal}
              className="far fa-bookmark ml-3 fa-lg mr-2"
            ></i>
            {/* <i className="fas fa-external-link-alt fa-lg"></i> */}
          </p>
        </div>
      </div>
    ) : (
      <div className="container">
        <div className="row justify-content-center">
          <img src={Spinner} alt="spinner" />
        </div>
      </div>
    );

    // Kanjis of radical
    const kanjis = radical.kanjis
      ? radical.kanjis.map((kanji) => {
          kanji.meaning = kanji.meaning.split("|");
          kanji.meaning = kanji.meaning.slice(0, 3);
          kanji.meaning = kanji.meaning.join(", ");

          return (
            <div className="row justify-content-center mt-5" key={kanji.id}>
              <div className="col-md-8">
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
                        {/* <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg mr-2"></i> */}
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

    // Model for radical addint to lists
    let addModal = this.state.lists
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
        <span className="mt-5">
          <Link to="/radicals" className="tag-link">
            Back
          </Link>
        </span>
        {this.state.radical ? (
          singleRadical
        ) : (
          <div className="container">
            <div className="row justify-content-center">
              <img src={Spinner} alt="spinner" />
            </div>
          </div>
        )}
        <hr />
        {this.state.radical.kanjis ? (
          <h4>kanjis ({radical.kanjis.length}) results</h4>
        ) : (
          ""
        )}
        {kanjis}
        <Modal show={this.state.show} onHide={this.handleClose}>
          <Modal.Header closeButton>
            <Modal.Title>Choose Radical List to add</Modal.Title>
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
            {/* <Button variant="primary" onClick={this.handleClose}>
                        Save Changes
                    </Button> */}
          </Modal.Footer>
        </Modal>
      </div>
    );
  }
}

export default RadicalDetails;
