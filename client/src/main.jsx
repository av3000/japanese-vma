import React from "react";
// Put any other imports below so that CSS from your
// components takes precedence over default styles.
import { createRoot } from "react-dom/client";
import "./assets/font-awesome/css/all.min.css";
import "./styles/index.scss";
import App from "./App";

const root = createRoot(document.getElementById("root"));
root.render(<App />);