import React, { Component } from "react";
import { Button, Modal } from "react-bootstrap";
import { Link } from "react-router-dom";
import { connect } from "react-redux";
import { apiCall } from "../../services/api";
import DefaultArticleImg from "../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg";
import AvatarImg from "../../assets/images/avatar-woman.svg";
import Spinner from "../../assets/images/spinner.gif";
import { hideLoader, showLoader } from "../../store/actions/application";
import CommentList from "../comment/CommentList";
import CommentForm from "../comment/CommentForm";
import { BASE_URL, HTTP_METHOD } from "../../shared/constants";
import { setSelectedArticle } from "../../store/actions/articles";

class ArticleDetails extends Component {
  _isMounted = false;

  constructor(props) {
    super(props);
    this.state = {
      article: null,
      lists: null,
      showBookmark: false,
      showPdf: false,
      showDelete: false,
      showStatus: false,
      tempStatus: false,
      isLoading: true,
    };

    this.likeArticle = this.likeArticle.bind(this);
    this.addComment = this.addComment.bind(this);
    this.deleteComment = this.deleteComment.bind(this);
    this.likeComment = this.likeComment.bind(this);
    this.editComment = this.editComment.bind(this);
    this.deleteArticle = this.deleteArticle.bind(this);
    this.downloadKanjisPdf = this.downloadKanjisPdf.bind(this);
    this.downloadWordsPdf = this.downloadWordsPdf.bind(this);
    this.toggleStatus = this.toggleStatus.bind(this);
    this.handleStatusChange = this.handleStatusChange.bind(this);

    this.openBookmarkModal = this.openBookmarkModal.bind(this);
    this.openPdfModal = this.openPdfModal.bind(this);
    this.openDeleteModal = this.openDeleteModal.bind(this);
    this.openStatusModal = this.openStatusModal.bind(this);

    this.handleBookmarkClose = this.handleBookmarkClose.bind(this);
    this.handlePdfClose = this.handlePdfClose.bind(this);
    this.handleDeleteModalClose = this.handleDeleteModalClose.bind(this);
    this.handleStatusModalClose = this.handleStatusModalClose.bind(this);

    this.getUserArticleLists = this.getUserArticleLists.bind(this);
  }

  articleId = this.props.match.params.article_id;

  componentWillUnmount() {
    this._isMounted = false;
  }

  async componentDidMount() {
    this._isMounted = true;

    if (this._isMounted) {
      const { selectedArticle } = this.props;

      if (!selectedArticle) {
        await this.getArticleDetails();
        if (this.props.currentUser.isAuthenticated) {
          await this.getUserRelationsToArticle();
          await this.getUserArticleLists();
        }
      } else {
        this.setState({ article: selectedArticle });
        if (this.props.currentUser.isAuthenticated) {
          await this.getUserRelationsToArticle();
          await this.getUserArticleLists();
        }
      }
    }
  }

  async getArticleDetails() {
    try {
      const url = `${BASE_URL}/api/article/${this.articleId}`;
      const data = await apiCall(HTTP_METHOD.GET, url);
      const { article } = data;

      if (!article) {
        this.props.history.push("/articles");
        return;
      }

      this.props.setSelectedArticle(article);

      this.setState({
        article,
        tempStatus: article.status,
        isLoading: true,
      });
    } catch (error) {
      console.log(error);
      this.setState({ isLoading: false });
      this.props.history.push("/articles");
    }
  }

  async getUserRelationsToArticle() {
    try {
      const userLike = await apiCall(
        HTTP_METHOD.POST,
        `${BASE_URL}/api/article/${this.articleId}/checklike`
      );

      this.setState((prevState) => ({
        article: {
          ...prevState.article,
          isLiked: userLike.isLiked,
          comments: prevState.article.comments.map((comment) => ({
            ...comment,
            isLiked: comment.likes.some(
              (like) => like.user_id === this.props.currentUser.user.id
            ),
          })),
        },
        isLoading: false,
      }));
    } catch (error) {
      console.log(error);
    }
  }

  async getUserArticleLists() {
    try {
      this.setState({ isLoading: true });
      const url = `${BASE_URL}/api/user/lists/contain`;
      const data = await apiCall(HTTP_METHOD.POST, url, {
        elementId: this.articleId,
      });
      this.setState({
        lists: data.lists.filter((list) => list.type === 9),
        isLoading: false,
      });
    } catch (error) {
      console.log(error);
      this.setState({ isLoading: false });
    }
  }

  async deleteArticle() {
    try {
      await apiCall(
        HTTP_METHOD.DELETE,
        `/api/article/${this.state.article.id}`
      );
      this.props.history.push("/articles");
    } catch (error) {
      console.log(error);
    }
  }

  async addToList(id) {
    try {
      this.setState({ show: !this.state.show });
      const url = `${BASE_URL}/api/user/list/additemwhileaway`;
      await apiCall(HTTP_METHOD.POST, url, {
        listId: id,
        elementId: this.articleId,
      });

      this.setState((prevState) => ({
        lists: prevState.lists.find((list) => {
          if (list.id === id) {
            list.elementBelongsToList = true;
          }
        }),
      }));
    } catch (error) {
      console.log(error);
    }
  }

  async removeFromList(id) {
    try {
      this.setState({ show: !this.state.show });
      const url = `${BASE_URL}/api/user/list/removeitemwhileaway`;
      await apiCall(HTTP_METHOD.POST, url, {
        listId: id,
        elementId: this.articleId,
      });

      this.setState((prevState) => ({
        lists: prevState.lists.find((list) => {
          if (list.id === id) {
            list.elementBelongsToList = false;
          }
        }),
      }));
    } catch (error) {
      console.log(error);
    }
  }

  async likeArticle(id) {
    try {
      if (!this.props.currentUser.isAuthenticated) {
        this.props.history.push("/login");
        return;
      }

      this.setState({ isLoading: true });

      const endpoint = this.state.article.isLiked === true ? "unlike" : "like";
      const url = `${BASE_URL}/api/article/${id}/${endpoint}`;
      await apiCall(HTTP_METHOD.POST, url);

      this.setState((prevState) => ({
        article: {
          ...prevState.article,
          isLiked: !prevState.article.isLiked,
          likesTotal: (prevState.article.likesTotal +=
            endpoint === "like" ? 1 : -1),
        },
        isLoading: false,
      }));
    } catch (error) {
      console.log(error);
      this.setState({ isLoading: false });
    }
  }

  async downloadKanjisPdf() {
    try {
      if (!this.props.currentUser.isAuthenticated) {
        this.props.history.push("/login");
        return;
      }

      this.props.dispatch(showLoader("Creating a PDF, please wait."));
      const url = `${BASE_URL}/api/article/${this.articleId}/kanjis-pdf`;
      const res = await apiCall(HTTP_METHOD.GET, url, {
        responseType: "blob",
      });

      this.props.dispatch(hideLoader());
      const file = new Blob([res.data], { type: "application/pdf" });
      const fileURL = URL.createObjectURL(file);
      window.open(fileURL);
    } catch (error) {
      console.log(error);
    }
  }

  async downloadWordsPdf() {
    try {
      if (!this.props.currentUser.isAuthenticated) {
        this.props.history.push("/login");
        return;
      }
      this.props.dispatch(showLoader("Creating a PDF, please wait."));
      const url = `${BASE_URL}/api/article/${this.articleId}/words-pdf`;
      const res = await apiCall(HTTP_METHOD.GET, url, {
        responseType: "blob",
      });
      this.props.dispatch(hideLoader());
      const file = new Blob([res.data], { type: "application/pdf" });
      const fileURL = URL.createObjectURL(file);
      window.open(fileURL);
    } catch (error) {
      console.log(error);
    }
  }

  async toggleStatus() {
    try {
      this.handleStatusModalClose();
      const res = await apiCall(
        HTTP_METHOD.POST,
        `/api/article/${this.articleId}/setstatus`,
        {
          status: this.state.tempStatus,
        }
      );

      this.setState((prevState) => ({
        article: {
          status: res.newStatus,
          tempStatus: res.newStatus,
        },
      }));
    } catch (error) {
      console.log(error);
    }
  }

  handleStatusChange(e) {
    const tempStatus = parseInt(e.target.value);
    const newState = Object.assign({}, this.state);
    newState.tempStatus = tempStatus;
    this.setState(newState);
  }

  openStatusModal() {
    if (!this.props.currentUser.isAuthenticated) {
      this.props.history.push("/login");
      return;
    }

    this.setState({ showStatus: !this.state.showStatus });
  }

  handleStatusModalClose() {
    this.setState({ showStatus: !this.state.showStatus });
  }

  openBookmarkModal() {
    if (!this.props.currentUser.isAuthenticated) {
      this.props.history.push("/login");
      return;
    }

    this.setState({ showBookmark: !this.state.showBookmark });
  }

  openPdfModal() {
    if (!this.props.currentUser.isAuthenticated) {
      this.props.history.push("/login");
      return;
    }

    this.setState({ showPdf: !this.state.showPdf });
  }

  handleDeleteModalClose() {
    this.setState({ showDelete: !this.state.showDelete });
  }

  openDeleteModal() {
    if (!this.props.currentUser.isAuthenticated) {
      this.props.history.push("/login");
      return;
    }

    this.setState({ showDelete: !this.state.showDelete });
  }

  handleBookmarkClose() {
    this.setState({ showBookmark: !this.state.showBookmark });
  }

  handlePdfClose() {
    this.setState({ showPdf: !this.state.showPdf });
  }

  async likeComment(commentId) {
    try {
      if (!this.props.currentUser.isAuthenticated) {
        this.props.history.push("/login");
        return;
      }

      this.setState({ isLoading: true });

      const theComment = this.state.article.comments.find(
        (comment) => comment.id === commentId
      );

      const endpoint = theComment.isLiked === true ? "unlike" : "like";
      const url = `${BASE_URL}/api/article/${this.articleId}/comment/commentId/${endpoint}`;

      await apiCall(HTTP_METHOD.POST, url);

      this.setState((prevState) => {
        const updatedComments = prevState.article.comments.map((comment) => {
          if (comment.id === commentId) {
            return {
              ...comment,
              isLiked: !comment.isLiked,
              likesTotal: comment.likesTotal + (endpoint === "like" ? 1 : -1),
            };
          }
          return comment;
        });

        return {
          article: {
            ...prevState.article,
            comments: updatedComments,
          },
          isLoading: false,
        };
      });
    } catch (error) {
      console.log(error);
      this.setState({ isLoading: false });
    }
  }

  addComment(comment) {
    this.setState((prevState) => ({
      article: {
        ...prevState.article,
        comments: [comment, ...prevState.article.comments],
      },
    }));
  }

  async deleteComment(commentId) {
    try {
      this.setState({ isLoading: true });

      await apiCall(
        "delete",
        `/api/article/${this.articleId}/comment/${commentId}`
      );

      this.setState((prevState) => ({
        article: {
          comments: prevState.article.comments.filter(
            (comment) => comment.id !== commentId
          ),
        },
      }));
    } catch (error) {
      console.log(error);
    }
  }

  editComment(commentId) {
    console.log(commentId);
  }

  render() {
    const { article } = this.state;
    const { currentUser } = this.props;
    const comments = article ? article.comments : "";

    let articleStatus = "";
    let articlePublicity = "";

    if (this.state.article) {
      if (this.state.article.status === 0) {
        articleStatus = "pending";
      } else if (this.state.article.status === 1) {
        articleStatus = "reviewing";
      } else if (this.state.article.status === 2) {
        articleStatus = "rejected";
      } else if (this.state.article.status === 3) {
        articleStatus = "approved";
      }

      if (this.state.article.publicity === 1) {
        articlePublicity = "public | ";
      } else {
        articlePublicity = "private | ";
      }
    }

    const singleArticle = article ? (
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-lg-8 ">
            <span className="row mt-4">
              <Link to="/articles" className="tag-link">
                {" "}
                <i className="fas fa-arrow-left"></i> Back
              </Link>
            </span>
            <h1 className="mt-4">{article.title_jp}</h1>
            <p className="text-muted">
              Posted on {article.jp_year} {article.jp_month} {article.jp_day}{" "}
              {article.jp_hour}
              <br />
              <span>{article.viewsTotal + 40} views</span> <br />
              {currentUser.user.id === article.user_id ||
              currentUser.user.isAdmin
                ? articlePublicity
                : ""}
              {currentUser.user.id === article.user_id ||
              currentUser.user.isAdmin
                ? articleStatus
                : ""}
            </p>
            <ul className="brand-icons mr-1 float-right d-flex">
              {currentUser.user.isAdmin ? (
                <li onClick={this.openStatusModal}>
                  <button>Review</button>
                </li>
              ) : (
                ""
              )}
              {currentUser.user.id === article.user_id ? (
                <li onClick={this.openDeleteModal}>
                  <button>
                    <i className="far fa-trash-alt fa-lg"></i>
                  </button>
                </li>
              ) : (
                ""
              )}
              {currentUser.user.id === article.user_id ? (
                <Link to={`/article/edit/${article.id}`}>
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
            <p className="lead">{article.content_jp} </p>
            <br />
            <p>
              {article.hashtags.map((tag) => (
                <span key={tag.id} className="tag-link" to="/">
                  {tag.content}{" "}
                </span>
              ))}
              <br />{" "}
              <a
                href={article.source_link}
                target="_blank"
                rel="noopener noreferrer"
              >
                original source
              </a>
            </p>
            <hr />
            <div>
              <div className="mr-1 float-left d-flex">
                <img src={AvatarImg} alt="book-japanese" />
                <p className="ml-3 mt-3">created by {article.userName}</p>
              </div>
              <div className="float-right d-flex">
                <p className="ml-3 mt-3">{article.likesTotal} likes &nbsp;</p>
                <ul className="brand-icons float-right d-flex">
                  {article.isLiked ? (
                    <li
                      onClick={() => this.likeArticle(article.id)}
                      disabled={this.state.loading}
                    >
                      <button>
                        <i className="fas fa-thumbs-up"></i>
                      </button>
                    </li>
                  ) : (
                    <li
                      onClick={() => this.likeArticle(article.id)}
                      disabled={this.state.loading}
                    >
                      <button>
                        <i className="far fa-thumbs-up"></i>
                      </button>
                    </li>
                  )}
                  <li onClick={this.openBookmarkModal}>
                    <button>
                      <i className="far fa-bookmark fa-lg"></i>
                    </button>
                  </li>
                  <li onClick={this.openPdfModal}>
                    <button>
                      <i className="fas fa-file-download fa-lg"></i>
                    </button>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    ) : (
      ""
    );

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
        {this.state.article ? (
          singleArticle
        ) : (
          <div className="container">
            <div className="row justify-content-center">
              <img src={Spinner} alt="spinner loading" />
            </div>
          </div>
        )}
        <br />
        <div className="row justify-content-center">
          {article ? (
            currentUser.isAuthenticated ? (
              <div className="col-lg-8">
                <hr />
                <h6>Share what's on your mind</h6>
                <CommentForm
                  addComment={this.addComment}
                  currentUser={currentUser}
                  objectId={this.state.article.id}
                  objectType="article"
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
                objectId={this.state.article.id}
                currentUser={currentUser}
                comments={comments}
                deleteComment={this.deleteComment}
                likeComment={this.likeComment}
                editComment={this.editComment}
              />
            ) : (
              ""
            )}
          </div>
        </div>

        {this.state.lists ? (
          <Modal
            show={this.state.showBookmark}
            onHide={this.handleBookmarkClose}
          >
            <Modal.Header closeButton>
              <Modal.Title>Choose List to add</Modal.Title>
            </Modal.Header>
            <Modal.Body>
              {addModal}
              <small>
                {" "}
                <Link to="/newlist">Want new list?</Link>{" "}
              </small>
            </Modal.Body>
            <Modal.Footer>
              <Button variant="secondary" onClick={this.handleBookmarkClose}>
                Close
              </Button>
            </Modal.Footer>
          </Modal>
        ) : (
          ""
        )}

        <Modal show={this.state.showPdf} onHide={this.handlePdfClose}>
          <Modal.Header closeButton>
            <Modal.Title>Choose which data you want to download.</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <button
              className="btn btn-outline brand-button"
              onClick={this.downloadKanjisPdf}
            >
              {" "}
              Kanji PDF{" "}
            </button>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={this.handlePdfClose}>
              Close
            </Button>
          </Modal.Footer>
        </Modal>

        <Modal
          show={this.state.showDelete}
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
                onClick={this.deleteArticle}
              >
                Yes, delete
              </Button>
            </div>
          </Modal.Footer>
        </Modal>

        {article ? (
          <Modal
            show={this.state.showStatus}
            onHide={this.handleStatusModalClose}
          >
            <Modal.Header closeButton>
              <Modal.Title>Are You Sure?</Modal.Title>
            </Modal.Header>
            <Modal.Footer>
              <div className="col-12">
                <select
                  name="tempStatus"
                  value={this.state.tempStatus}
                  className="form-control form-control-sm w-75 mb-2"
                  onChange={this.handleStatusChange}
                >
                  <option value="0">Pending</option>
                  <option value="1">Review</option>
                  <option value="2">Reject</option>
                  <option value="3">Approve</option>
                </select>
                <Button
                  variant="secondary"
                  className="float-left"
                  onClick={this.handleStatusModalClose}
                >
                  Cancel
                </Button>
                <Button
                  variant="success"
                  className="float-right"
                  onClick={this.toggleStatus}
                >
                  Submit
                </Button>
              </div>
            </Modal.Footer>
          </Modal>
        ) : (
          ""
        )}
      </div>
    );
  }
}

const mapStateToProps = (state) => ({
  selectedArticle: state.articles.selectedArticle,
});

const mapDispatchToProps = {
  setSelectedArticle,
};

export default connect(mapStateToProps, mapDispatchToProps)(ArticleDetails);
