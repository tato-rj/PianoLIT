import { bootGame } from "../duel/DuelClient.js";
import { ChordsLab } from "./chordslab/ChordsLab.js";

const options = window.__challengeOptions || {};
const clefUrls = window.__clefUrls || null;

bootGame((duelOptions) => new ChordsLab({
  ...(duelOptions || options),
  clefUrls,
}));
