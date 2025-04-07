// @ts-nocheck
import React, { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { Modal } from "react-bootstrap";
import { useDispatch, useSelector } from "react-redux";

import { Button } from "@/components/shared/Button";
import { Icon } from "@/components/shared/Icon";
import { Link } from "@/components/shared/Link";

import { apiCall } from "@/services/api";
import DefaultArticleImg from "@/assets/images/smartphone-screen-with-art-photo-gallery-application-3850271-mid.jpg";
import AvatarImg from "@/assets/images/avatar-woman.svg";
import Spinner from "@/assets/images/spinner.gif";
import { hideLoader, showLoader } from "@/store/actions/application";
import CommentList from "@/components/features/comment/CommentList";
import CommentForm from "@/components/features/comment/CommentForm";
import ListItems from "@/components/features/SavedList/SavedListItems";
import { BASE_URL, HTTP_METHOD, ObjectTemplates } from "@/shared/constants";
import Hashtags from "@/components/ui/hashtags";

const SavedListDetails: React.FC = () => {
  const [list, setList] = useState(null);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [editToggle, setEditToggle] = useState(false);
  const [editToggleHeading, setEditToggleHeading] = useState("Edit");

  const currentUser = useSelector((state) => state.currentUser);
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { list_id } = useParams();

  useEffect(() => {
    getListWithAuth();
  }, [currentUser.isAuthenticated]);

  const getListWithAuth = async () => {
    try {
      const url = `${BASE_URL}/api/list/${list_id}`;
      const res = await apiCall(HTTP_METHOD.GET, url);
      const listData = res.list;
      setList(listData);

      if (!listData) {
        navigate("/lists");
      } else if (currentUser.isAuthenticated) {
        const likeRes = await apiCall(
          HTTP_METHOD.POST,
          `/api/list/${list_id}/checklike`
        );
        setList((prevList) => ({ ...prevList, isLiked: likeRes.isLiked }));
      }

      if (currentUser.isAuthenticated) {
        setList((prevList) => ({
          ...prevList,
          comments: prevList.comments.map((comment) => {
            const youLikedIt = comment.likes.some(
              (like) => like.user_id === currentUser.user.id
            );
            return { ...comment, isLiked: youLikedIt };
          }),
        }));
      }
    } catch (err) {
      console.error(err);
    }
  };

  const removeFromList = async (id) => {
    const url = `${BASE_URL}/api/user/list/removeitemwhileaway`;
    try {
      await apiCall(HTTP_METHOD.POST, url, {
        listId: list_id,
        elementId: id,
      });
      setList((prevList) => ({
        ...prevList,
        listItems: prevList.listItems.filter((item) => item.id !== id),
      }));
    } catch (err) {
      console.error(err);
    }
  };

  const deleteList = async () => {
    try {
      await apiCall(HTTP_METHOD.DELETE, `/api/list/${list_id}`);
      navigate("/lists");
    } catch (err) {
      console.error(err);
    }
  };

  const downloadPdf = async () => {
    if (!currentUser.isAuthenticated) {
      navigate("/login");
    } else if (list.listItems.length === 0) {
      dispatch(showLoader("There are no items in the list!"));
      setTimeout(() => {
        dispatch(hideLoader());
      }, 2500);
    } else {
      dispatch(showLoader("Creating a PDF, please wait."));
      let endpoint = "";
      if (
        list.type === ObjectTemplates.KNOWNRADICALS ||
        list.type === ObjectTemplates.RADICALS
      ) {
        endpoint = "radicals-pdf";
      } else if (
        list.type === ObjectTemplates.KNOWNKANJIS ||
        list.type === ObjectTemplates.KANJIS
      ) {
        endpoint = "kanjis-pdf";
      } else if (
        list.type === ObjectTemplates.KNOWNWORDS ||
        list.type === ObjectTemplates.WORDS
      ) {
        endpoint = "words-pdf";
      } else if (
        list.type === ObjectTemplates.KNOWNSENTENCES ||
        list.type === ObjectTemplates.SENTENCES
      ) {
        endpoint = "sentences-pdf";
      } else if (list.type === ObjectTemplates.ARTICLES) {
        dispatch(hideLoader());
        return;
      }

      const url = `${BASE_URL}/api/list/${list_id}/${endpoint}`;

      try {
        const res = await apiCall(HTTP_METHOD.GET, url, {
          responseType: "blob",
        });
        dispatch(hideLoader());
        const file = new Blob([res], { type: "application/pdf" });
        const fileURL = URL.createObjectURL(file);
        window.open(fileURL);
      } catch (err) {
        console.error(err);
      }
    }
  };

  const likeList = async () => {
    if (!currentUser.isAuthenticated) {
      navigate("/login");
    } else {
      const endpoint = list.isLiked ? "unlike" : "like";
      const url = `${BASE_URL}/api/list/${list_id}/${endpoint}`;
      try {
        await apiCall(HTTP_METHOD.POST, url);
        setList((prevList) => ({
          ...prevList,
          isLiked: !prevList.isLiked,
          likesTotal: prevList.isLiked
            ? prevList.likesTotal - 1
            : prevList.likesTotal + 1,
        }));
      } catch (err) {
        console.error(err);
      }
    }
  };

  const toggleListEdit = () => {
    if (currentUser.user.id === list.user_id) {
      const newHeading = !editToggle ? "End" : "Edit";
      setEditToggle(!editToggle);
      setEditToggleHeading(newHeading);
    } else {
      navigate("/login");
    }
  };

  const handleDeleteModalClose = () => {
    setShowDeleteModal(false);
  };

  const openDeleteModal = () => {
    if (!currentUser.isAuthenticated) {
      navigate("/login");
    } else {
      setShowDeleteModal(true);
    }
  };

  const likeComment = async (commentId) => {
    if (!currentUser.isAuthenticated) {
      navigate("/login");
    } else {
      const comment = list.comments.find((c) => c.id === commentId);
      const endpoint = comment.isLiked ? "unlike" : "like";
      const url = `${BASE_URL}/api/list/${list_id}/comment/${commentId}/${endpoint}`;
      try {
        await apiCall(HTTP_METHOD.POST, url);
        setList((prevList) => {
          const updatedComments = prevList.comments.map((c) =>
            c.id === commentId
              ? {
                  ...c,
                  isLiked: !c.isLiked,
                  likesTotal: c.isLiked ? c.likesTotal - 1 : c.likesTotal + 1,
                }
              : c
          );
          return { ...prevList, comments: updatedComments };
        });
      } catch (err) {
        console.error(err);
      }
    }
  };

  const addComment = (comment) => {
    setList((prevList) => ({
      ...prevList,
      comments: [comment, ...prevList.comments],
    }));
  };

  const deleteComment = async (commentId) => {
    try {
      await apiCall(
        HTTP_METHOD.DELETE,
        `/api/list/${list.id}/comment/${commentId}`
      );
      setList((prevList) => ({
        ...prevList,
        comments: prevList.comments.filter((c) => c.id !== commentId),
      }));
    } catch (err) {
      console.error(err);
    }
  };

  const editComment = (commentId) => {};

  if (!list) {
    return (
      <div className="container">
        <div className="row justify-content-center">
          <img src={Spinner} alt="Loading..." />
        </div>
      </div>
    );
  }

  return (
    <div className="container">
      <>
        <div className="row justify-content-center">
          <div className="col-lg-8 col-md-12 col-sm-12">
            <span className="row mt-4">
              <Link to="/lists" className="tag-link">
                {" "}
                <i className="fas fa-arrow-left"></i> Back
              </Link>
            </span>
            <h1 className="mt-4">{list.title}</h1>
            <div className="row text-muted w-100 mb-3 justify-content-between">
              <div className="text-muted">
                {list.created_at}
                <br />
                <span>{list.viewsTotal} views</span>
                {currentUser.user.id === list.user_id
                  ? list.publicity === 1
                    ? " | public"
                    : " | private"
                  : ""}
                <br /> <strong>{list.listType} List</strong>
              </div>
              <div>
                {currentUser.user.id === list.user_id && (
                  <>
                    <Button
                      onClick={openDeleteModal}
                      variant="ghost"
                      size="md"
                      hasOnlyIcon
                    >
                      <Icon name="trashbinSolid" size="md" />
                    </Button>

                    <Button
                      as={Link}
                      to={`/list/edit/${list.id}`}
                      variant="ghost"
                      size="md"
                      hasOnlyIcon
                    >
                      <Icon name="penSolid" size="md" />
                    </Button>
                  </>
                )}
              </div>
            </div>

            <img
              className="img-fluid rounded mb-3"
              src={DefaultArticleImg}
              alt="default-article-img"
            />
            <p className="lead">{list.content}</p>
            <br />
            <Hashtags hashtags={list.hashtags} />
            <hr />
            <div>
              <div className="mr-1 float-left d-flex">
                <img src={AvatarImg} alt="avatar" />
                <p className="ml-3 mt-3">created by {list.userName}</p>
              </div>
              <div className="float-right d-flex align-items-center">
                <p>{list.likesTotal} likes &nbsp;</p>
                <Button
                  onClick={likeList}
                  variant="ghost"
                  size="md"
                  hasOnlyIcon
                >
                  <Icon size="md" name="thumbsUpSolid" />
                </Button>

                {list.type !== ObjectTemplates.ARTICLES && (
                  <Button
                    size="md"
                    variant="ghost"
                    hasOnlyIcon
                    onClick={downloadPdf}
                  >
                    <Icon size="md" name="filePdfSolid" />
                  </Button>
                )}
              </div>
            </div>
          </div>
        </div>
        {/* List Items */}
        <div className="row justify-content-center">
          <div className="col-lg-8">
            {list.listItems.length > 0 && (
              <>
                <div className="mt-3 mb-2">
                  {currentUser.isAuthenticated &&
                    currentUser.user.id === list.user_id && (
                      <Button
                        onClick={toggleListEdit}
                        size="sm"
                        variant={editToggle ? "success" : "ghost"}
                      >
                        {editToggleHeading}
                      </Button>
                    )}
                </div>
                <ListItems
                  editToggle={editToggle}
                  objects={list.listItems}
                  removeFromList={removeFromList}
                  listType={list.type}
                  currentUser={currentUser}
                  listUserId={list.user_id}
                />
              </>
            )}
          </div>
        </div>
        <div className="row justify-content-center">
          {currentUser.isAuthenticated ? (
            <div className="col-lg-8">
              <hr />
              <h6>Share what's on your mind</h6>
              <CommentForm
                addComment={addComment}
                currentUser={currentUser}
                objectId={list.id}
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
          )}
          <div className="col-lg-8">
            {list.comments && (
              <CommentList
                objectId={list.id}
                currentUser={currentUser}
                comments={list.comments}
                deleteComment={deleteComment}
                likeComment={likeComment}
                editComment={editComment}
              />
            )}
          </div>
        </div>
        <Modal show={showDeleteModal} onHide={handleDeleteModalClose}>
          <Modal.Header closeButton>
            <Modal.Title>Are You Sure?</Modal.Title>
          </Modal.Header>
          <Modal.Footer>
            <div className="col-12">
              <Button
                variant="secondary"
                className="float-left"
                onClick={handleDeleteModalClose}
              >
                Cancel
              </Button>
              <Button
                variant="danger"
                className="float-right"
                onClick={deleteList}
              >
                Yes, delete
              </Button>
            </div>
          </Modal.Footer>
        </Modal>
      </>
    </div>
  );
};

export default SavedListDetails;
