import { bootGame } from "../duel/DuelClient.js";
import { NotePython } from "./notepython/NotePython.js";

const options = window.__challengeOptions || {};

bootGame((duelOptions) => new NotePython({
  ...(duelOptions || options),
}));
