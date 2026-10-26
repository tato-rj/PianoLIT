import { bootGame } from "../duel/DuelClient.js";
import { ChordDetective } from "./chorddetective/ChordDetective.js";

const options = window.__challengeOptions || {};
const clefUrls = window.__clefUrls || null;

bootGame((duelOptions) => new ChordDetective({
  ...(duelOptions || options),
  clefUrls,
}));
