import React, { Component } from "react";
import axios from "axios";
import Moment from "react-moment";
import { Button, ButtonGroup, Modal } from "react-bootstrap";
import { Link } from "react-router-dom";
import { connect } from "react-redux";

import { apiCall } from "../../services/api";
import DefaultArticleImg from "../../assets/images/smartphone-screen-with-art-photo-gallery-application-3850271-mid.jpg";
import AvatarImg from "../../assets/images/avatar-woman.svg";
import Spinner from "../../assets/images/spinner.gif";
import { hideLoader, showLoader } from "../../store/actions/application";
import CommentList from "../comment/CommentList";
import CommentForm from "../comment/CommentForm";
import ListItems from "./ListItems";
import { BASE_URL, ObjectTemplates } from "../../shared/constants";
import Hashtags from "../ui/hashtags";

class ListDetails extends Component {
  constructor(props) {
    super(props);
    this.state = {
      list: null,
      showDeleteModal: false,
      editToggle: false,
      editToggleHeading: "Edit",
    };

    this.deleteList = this.deleteList.bind(this);
    this.likeList = this.likeList.bind(this);
    this.addComment = this.addComment.bind(this);
    this.deleteComment = this.deleteComment.bind(this);
    this.likeComment = this.likeComment.bind(this);
    this.editComment = this.editComment.bind(this);
    this.downloadPdf = this.downloadPdf.bind(this);

    this.openDeleteModal = this.openDeleteModal.bind(this);
    this.handleDeleteModalClose = this.handleDeleteModalClose.bind(this);
    this.removeFromList = this.removeFromList.bind(this);
    this.toggleListEdit = this.toggleListEdit.bind(this);
  }

  listId = this.props.match.params.list_id;

  componentDidMount() {
    this.getListWithAuth();
  }

  getListWithAuth() {
    const url = BASE_URL + "/api/list/" + this.listId;

    axios
      .get(url)
      .then((res) => {
        this.setState({
          list: res.data.list,
        });

        return res.data.list;
      })
      .then((list) => {
        if (!list) {
          this.props.history.push("/lists");
        } else if (this.props.currentUser.isAuthenticated) {
          return apiCall("post", `/api/list/${this.listId}/checklike`).then(
            (res) => {
              const newState = Object.assign({}, this.state);
              newState.list.isLiked = res.isLiked;
              this.setState(newState);
            }
          );
        }
      })
      .then((res) => {
        const newState = Object.assign({}, this.state);
        if (this.props.currentUser.isAuthenticated) {
          newState.list.comments.map((comment) => {
            const youLikedIt = comment.likes.find(
              (like) => like.user_id === this.props.currentUser.user.id
            );
            if (youLikedIt) {
              comment.isLiked = true;
            } else {
              comment.isLiked = false;
            }
          });

          this.setState(newState);
        }
      })
      .catch((err) => {
        console.log(err);
      });
  }

  removeFromList(id) {
    const url = BASE_URL + "/api/user/list/removeitemwhileaway";
    axios
      .post(url, {
        listId: this.ListId,
        elementId: id,
      })
      .then((res) => {
        let newState = Object.assign({}, this.state);
        newState.list.listItems = newState.list.listItems.filter(
          (item) => item.id !== id
        );
        this.setState(newState);
      })
      .catch((err) => console.log(err));
  }

  async deleteList() {
    try {
      await apiCall("delete", `/api/list/${this.ListId}`);
      this.props.history.push("/lists");
    } catch (err) {
      console.log(err);
    }
  }

  downloadPdf() {
    if (!this.props.currentUser.isAuthenticated) {
      this.props.history.push("/login");
    } else if (this.state.list.listItems.length === 0) {
      this.props.dispatch(showLoader("There are no items in the list!"));
      setTimeout(() => {
        this.props.dispatch(hideLoader());
      }, 2500);
    } else {
      this.props.dispatch(showLoader("Creating a PDF, please wait."));
      let endpoint = "";
      if (this.state.list.type === 1 || this.state.list.type === 5) {
        endpoint = "radicals-pdf";
      } else if (this.state.list.type === 2 || this.state.list.type === 6) {
        endpoint = "kanjis-pdf";
      } else if (this.state.list.type === 3 || this.state.list.type === 7) {
        endpoint = "words-pdf";
      } else if (this.state.list.type === 4 || this.state.list.type === 8) {
        endpoint = "sentences-pdf";
      } else if (this.state.list.type === 9) {
        return;
      }

      const url = BASE_URL + "/api/list/" + this.ListId + "/" + endpoint;

      axios
        .get(url, {
          responseType: "blob",
        })
        .then((res) => {
          this.props.dispatch(hideLoader());
          const file = new Blob([res.data], { type: "application/pdf" });
          const fileURL = URL.createObjectURL(file);
          window.open(fileURL);
        })
        .catch((err) => {
          console.log(err);
        });
    }
  }

  likeList() {
    if (!this.props.currentUser.isAuthenticated) {
      this.props.history.push("/login");
    } else {
      let endpoint = this.state.list.isLiked === true ? "unlike" : "like";
      const url = BASE_URL + "/api/list/" + this.ListId + "/" + endpoint;

      axios
        .post(url)
        .then((res) => {
          let newState = Object.assign({}, this.state);

          if (endpoint === "unlike") {
            newState.list.isLiked = !newState.list.isLiked;
            newState.list.likesTotal -= 1;
            this.setState(newState);
          } else if (endpoint === "like") {
            newState.list.isLiked = !newState.list.isLiked;
            newState.list.likesTotal += 1;
            this.setState(newState);
          }
        })
        .catch((err) => {
          console.log(err);
        });
    }
  }

  toggleListEdit() {
    if (this.props.currentUser.user.id === this.state.list.user_id) {
      let heading = this.state.editToggle ? "Edit" : "End";
      this.setState({
        editToggle: !this.state.editToggle,
        editToggleHeading: heading,
      });
    } else {
      this.props.history.push("/login");
    }
  }

  handleDeleteModalClose() {
    this.setState({ showDeleteModal: !this.state.showDeleteModal });
  }

  openDeleteModal() {
    if (this.props.currentUser.isAuthenticated === false) {
      this.props.history.push("/login");
    } else {
      this.setState({ showDeleteModal: !this.state.showDeleteModal });
    }
  }

  likeComment(commentId) {
    if (!this.props.currentUser.isAuthenticated) {
      this.props.history.push("/login");
    } else {
      let theComment = this.state.list.comments.find(
        (comment) => comment.id === commentId
      );

      let endpoint = theComment.isLiked === true ? "unlike" : "like";
      const url =
        BASE_URL +
        "/api/list/" +
        this.ListId +
        "/comment/" +
        commentId +
        "/" +
        endpoint;

      axios
        .post(url)
        .then((res) => {
          const newState = Object.assign({}, this.state);
          const index = this.state.list.comments.findIndex(
            (comment) => comment.id === commentId
          );
          newState.list.comments[index].isLiked =
            !newState.list.comments[index].isLiked;

          if (endpoint === "unlike") {
            newState.list.comments[index].likesTotal -= 1;
          } else if (endpoint === "like") {
            newState.list.comments[index].likesTotal += 1;
          }

          this.setState(newState);
        })
        .catch((err) => {
          console.log(err);
        });
    }
  }

  addComment(comment) {
    const newState = Object.assign({}, this.state);
    newState.list.comments.unshift(comment);
    this.setState(newState);
  }

  deleteComment(commentId) {
    return apiCall(
      "delete",
      `/api/list/${this.state.list.id}/comment/${commentId}`
    )
      .then((res) => {
        const newState = Object.assign({}, this.state);
        newState.list.comments = newState.list.comments.filter(
          (comment) => comment.id !== commentId
        );
        this.setState(newState);
      })
      .catch((err) => {
        console.log(err);
      });
  }

  editComment(commentId) {
    console.log("editComment");
    console.log(commentId);
  }

  render() {
    const { list } = this.state;
    const { currentUser } = this.props;
    const comments = list ? list.comments : "";

    const singleList = list ? (
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-lg-8 col-md-12 col-sm-12">
            <span className="row mt-4">
              <Link to="/lists" className="tag-link">
                {" "}
                <i className="fas fa-arrow-left"></i> Back
              </Link>
            </span>
            <h1 className="mt-4">{list.title}</h1>
            <p className="text-muted">
              {" "}
              <Moment className="text-muted" format="Do MMM YYYY">
                {list.created_at}
              </Moment>
              <br />
              <span>{list.viewsTotal + 40} views</span>
              {currentUser.user.id === list.user_id
                ? list.publicity === 1
                  ? " | public"
                  : " | private"
                : ""}
              <br /> <strong> {list.listType} List</strong>
            </p>
            <ul className="brand-icons mr-1 float-right d-flex">
              {currentUser.user.id === list.user_id ? (
                <li onClick={this.openDeleteModal}>
                  <button>
                    <i className="far fa-trash-alt fa-lg"></i>
                  </button>
                </li>
              ) : (
                ""
              )}
              {currentUser.user.id === list.user_id ? (
                <Link to={`/list/edit/${list.id}`}>
                  <li>
                    <button>
                      <i className="fas fa-pen-alt fa-lg"></i>
                    </button>
                  </li>
                </Link>
              ) : (
                ""
              )}
            </ul>

            <img
              className="img-fluid rounded mb-3"
              src={DefaultArticleImg}
              alt="default-article-img"
            />
            <p className="lead">{list.content}Description </p>
            <br />
            <Hashtags hashtags={list.hashtags} />
            <hr />
            <div>
              <div className="mr-1 float-left d-flex">
                <img src={AvatarImg} alt="book-japanese" />
                <p className="ml-3 mt-3">created by {list.userName}</p>
              </div>
              <div className="float-right d-flex align-items-center">
                <p className="ml-3 mt-3">{list.likesTotal} likes &nbsp;</p>
                <ButtonGroup aria-label="List actions" className="brand-icons">
                  <Button
                    onClick={this.likeList}
                    variant="outline-primary"
                    className={
                      list.isLiked
                        ? "btn btn-outline brand-button liked-button"
                        : "btn btn-outline brand-button"
                    }
                  >
                    <i
                      className={
                        list.isLiked ? "fas fa-thumbs-up" : "far fa-thumbs-up"
                      }
                    ></i>
                  </Button>
                  {list.type !== ObjectTemplates.ARTICLES ? (
                    <Button
                      onClick={this.downloadPdf}
                      className="btn btn-outline brand-button"
                      variant="outline-primary"
                    >
                      <i className="fas fa-file-download fa-lg"></i>
                    </Button>
                  ) : (
                    ""
                  )}
                </ButtonGroup>
              </div>
            </div>
          </div>
        </div>
      </div>
    ) : (
      ""
    );

    return (
      <div className="container">
        {singleList ? (
          singleList
        ) : (
          <div className="container">
            <div className="row justify-content-center">
              <img src={Spinner} alt="spinner loading" />
            </div>
          </div>
        )}
        <div className="row justify-content-center">
          <div className="col-lg-8">
            {list && list.listItems.length > 0 ? (
              <React.Fragment>
                <div className="mt-3 mb-2">
                  {currentUser.isAuthenticated &&
                  currentUser.user.id === list.user_id ? (
                    this.state.editToggle ? (
                      <button
                        onClick={this.toggleListEdit}
                        className="btn btn-sm btn-success"
                      >
                        {" "}
                        {this.state.editToggleHeading}{" "}
                      </button>
                    ) : (
                      <button
                        onClick={this.toggleListEdit}
                        className="btn btn-sm btn-light"
                      >
                        {" "}
                        {this.state.editToggleHeading}{" "}
                      </button>
                    )
                  ) : (
                    ""
                  )}
                </div>
                <ListItems
                  editToggle={this.state.editToggle}
                  objects={this.state.list.listItems}
                  removeFromList={this.removeFromList}
                  listType={this.state.list.type}
                  currentUser={this.props.currentUser}
                  listUserId={this.state.list.user_id}
                />
              </React.Fragment>
            ) : (
              ""
            )}
          </div>
        </div>

        <div className="row justify-content-center">
          {list ? (
            currentUser.isAuthenticated ? (
              <div className="col-lg-8">
                <hr />
                <h6>Share what's on your mind</h6>
                <CommentForm
                  addComment={this.addComment}
                  currentUser={currentUser}
                  objectId={this.state.list.id}
                  objectType="list"
                />
              </div>
            ) : (
              <div className="col-lg-8">
                <hr />
                <h6>
                  You need to
                  <Link to="/login"> login </Link>
                  to comment
                </h6>
              </div>
            )
          ) : (
            ""
          )}
          <div className="col-lg-8">
            {comments ? (
              <CommentList
                objectId={this.state.list.id}
                currentUser={currentUser}
                comments={this.state.list.comments}
                deleteComment={this.deleteComment}
                likeComment={this.likeComment}
                editComment={this.editComment}
              />
            ) : (
              ""
            )}
          </div>

          <Modal
            show={this.state.showDeleteModal}
            onHide={this.handleDeleteModalClose}
          >
            <Modal.Header closeButton>
              <Modal.Title>Are You Sure?</Modal.Title>
            </Modal.Header>
            <Modal.Footer>
              <div className="col-12">
                <Button
                  variant="secondary"
                  className="float-left"
                  onClick={this.handleDeleteModalClose}
                >
                  Cancel
                </Button>
                <Button
                  variant="danger"
                  className="float-right"
                  onClick={this.deleteList}
                >
                  Yes, delete
                </Button>
              </div>
            </Modal.Footer>
          </Modal>
        </div>
      </div>
    );
  }
}
const mapStateToProps = (state) => ({});

export default connect(mapStateToProps)(ListDetails);
