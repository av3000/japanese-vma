import React from "react";
import { Routes, Route, useLocation, useNavigate } from "react-router-dom";
import { useSelector, useDispatch } from "react-redux";
import { authUser } from "../store/actions/auth";
import { removeError } from "../store/actions/errors";
import ProtectedRoute from "../helpers/PrivateRoute";
import AuthForm from "../components/authform/AuthForm";
import PageNotFound from "../components/errors/PageNotFound";
// Landing
import Homepage from "../components/homepage/Homepage";
// Articles
import ArticleDetails from "../components/article/ArticleDetails";
import ArticleTimeline from "../components/article/ArticleTimeline";
import ArticleForm from "../components/article/ArticleForm";
import ArticleEdit from "../components/article/ArticleEdit";
// Lists
import ListTimeline from "../components/list/ListTimeline";
import ListDetails from "../components/list/ListDetails";
import ListForm from "../components/list/ListForm";
import ListEdit from "../components/list/ListEdit";
// Radicals
import RadicalDetails from "../components/radical/RadicalDetails";
import RadicalTimeline from "../components/radical/RadicalTimeline";
// Kanjis
import KanjiDetails from "../components/kanji/KanjiDetails";
import KanjiTimeline from "../components/kanji/KanjiTimeline";
// Words
import WordDetails from "../components/word/WordDetails";
import WordTimeline from "../components/word/WordTimeline";
// Sentences
import SentenceDetails from "../components/sentence/SentenceDetails";
import SentenceTimeline from "../components/sentence/SentenceTimeline";
// Community
import PostDetails from "../components/post/PostDetails";
import PostTimeline from "../components/post/PostTimeline";
import PostForm from "../components/post/PostForm";
import PostEdit from "../components/post/PostEdit";
// Dashboard
import DashboardTimeline from "../components/dashboard/DashboardTimeline";

const Main = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const currentUser = useSelector(state => state.currentUser);
  const errors = useSelector(state => state.errors);

  const handleAuthUser = (userData) => {
    return dispatch(authUser(userData));
  };

  const handleRemoveError = () => {
    dispatch(removeError());
  };

  return (
    <div>
      <Routes>
        <Route path="/" element={<Homepage />} />
        
        <Route 
          path="/register" 
          element={
            <AuthForm
              onAuth={handleAuthUser}
              removeError={handleRemoveError}
              signUp
              errors={errors}
              buttonText="Sign up"
              heading="Join community today."
              navigate={navigate}
              location={location}
            />
          } 
        />
        
        <Route 
          path="/login" 
          element={
            <AuthForm
              onAuth={handleAuthUser}
              removeError={handleRemoveError}
              errors={errors}
              buttonText="Log in"
              heading="Welcome back."
              navigate={navigate}
              location={location}
            />
          } 
        />

        {/* Protected Routes */}
        <Route element={<ProtectedRoute />}>
          <Route path="/newarticle" element={<ArticleForm />} />
          <Route path="/article/edit/:article_id" element={<ArticleEdit />} />
          <Route 
            path="/newlist" 
            element={
              <ListForm 
                removeError={handleRemoveError}
                errors={errors}
              />
            } 
          />
          <Route path="/list/edit/:list_id" element={<ListEdit />} />
          <Route path="/community/edit/:post_id" element={<PostEdit />} />
          <Route 
            path="/newpost" 
            element={
              <PostForm
                removeError={handleRemoveError}
                errors={errors}
              />
            } 
          />
          <Route path="/dashboard" element={<DashboardTimeline />} />
        </Route>

        {/* Public Routes */}
        <Route path="/articles" element={<ArticleTimeline />} />
        <Route path="/article/:article_id" element={<ArticleDetails />} />
        <Route path="/lists" element={<ListTimeline />} />
        <Route 
          path="/list/:list_id" 
          element={<ListDetails currentUser={currentUser} />} 
        />
        <Route 
          path="/radicals" 
          element={<RadicalTimeline currentUser={currentUser} />} 
        />
        <Route 
          path="/radical/:radical_id" 
          element={<RadicalDetails currentUser={currentUser} />} 
        />
        <Route path="/kanjis" element={<KanjiTimeline />} />
        <Route 
          path="/kanji/:kanji_id" 
          element={<KanjiDetails currentUser={currentUser} />} 
        />
        <Route path="/words" element={<WordTimeline />} />
        <Route 
          path="/word/:word_id" 
          element={<WordDetails currentUser={currentUser} />} 
        />
        <Route path="/sentences" element={<SentenceTimeline />} />
        <Route 
          path="/sentence/:sentence_id" 
          element={<SentenceDetails currentUser={currentUser} />} 
        />
        <Route path="/community" element={<PostTimeline />} />
        <Route 
          path="/community/:post_id" 
          element={<PostDetails currentUser={currentUser} />} 
        />

        {/* Not found component */}
        <Route path="*" element={<PageNotFound />} />
      </Routes>
    </div>
  );
};

export default Main;